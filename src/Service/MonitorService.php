<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Sdk\Exception\CancellationFailed;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\TransactionStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Command\PollCommand;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Exception\DatabaseException;
use Twint\Woo\Exception\PaymentException;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Monitor\MonitoringStatus;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use Twint\Woo\Service\Express\ExpressOrderService;
use WC_Logger_Interface;

/**
 * @method ExpressOrderService getOrderService()
 * @method ClientBuilder getBuilder()
 * @method PairingRepository getRepository()
 * @method PairingService getPairingService()
 * @method TransactionRepository getLogRepository()
 * @method ApiService getApi()
 */
class MonitorService
{
    use LazyLoadTrait;

    private const CONFIRM_GRACE_SECONDS = 20;

    protected static array $lazyLoads = [
        'orderService',
        'builder',
        'repository',
        'pairingService',
        'logRepository',
        'api',
    ];

    public function __construct(
        private Lazy|PairingRepository       $repository,
        private Lazy|TransactionRepository   $logRepository,
        private Lazy|ClientBuilder           $builder,
        private readonly WC_Logger_Interface $logger,
        private Lazy|PairingService          $pairingService,
        private Lazy|ApiService              $api,
        private Lazy|ExpressOrderService     $orderService,
    ) {
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function monitors(): void
    {
        $pairings = $this->getRepository()
            ->loadInProcessPairings();

        /** @var Pairing $pairing */
        foreach ($pairings as $pairing) {
            // Skip pairings actively being polled by browser (transient set by PaymentStatusAction)
            if (get_transient('twint_http_poll_' . $pairing->getId())) {
                continue;
            }

            try {
                $this->monitor($pairing);
            } catch (Throwable $e) {
                // Silent error to allow process handle next Pairings
                $this->logger->error(
                    "TWINT MonitorService::monitors: cli error {$pairing->getId()} {$pairing->getToken()} {$e->getMessage()}",
                    [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]
                );
            }
        }

        // Reconciliation sweep: settle orders whose process died between
        // pairing terminal status and payment_complete().
        // Covers both Regular (SUCCESS) and Express (PAID) orphans.
        // Requires v2 schema (order_settled column) — no sweep in legacy mode (Y3).
        if ($this->getRepository()->isSchemaReady()) {
            $this->settleOrphanedPairings();
        }
    }

    /**
     * @throws Throwable
     */
    public function monitor(Pairing $pairing): MonitoringStatus
    {
        if ($pairing->isFinished()) {
            return MonitoringStatus::fromPairing($pairing);
        }

        $cloned = clone $pairing;

        if ($pairing->getIsExpress()) {
            $status = $this->monitorExpress($pairing, $cloned);

            // Re-drivable: capture once the customer has authorised, not only on the paid
            // edge, so a retry can recover a pairing whose worker died mid-capture.
            $readyToCapture = $status->paid()
                || ($pairing->getCustomerData() !== [] && !$cloned->isFinished());

            if ($readyToCapture) {
                // Only one worker may capture a pairing; the loser polls again.
                if (!$this->getRepository()->acquireConfirmLock($pairing->getId())) {
                    return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                }

                try {
                    // A sibling worker may have already settled it.
                    $fresh = $this->getRepository()->get($pairing->getId());
                    if ($fresh instanceof Pairing && ($fresh->isFinished() || $fresh->isSuccessful())) {
                        return MonitoringStatus::fromPairing($fresh);
                    }

                    $this->getRepository()->markAsOrdering($pairing->getId());
                    $this->getOrderService()->setMonitor($this);
                    $this->getOrderService()->update($cloned);
                    $status->addExtra('order', $pairing->getWcOrderId());

                    $this->logger->info("TWINT MonitorService::monitor: EC {$pairing->getId()} mark as paid", [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]);

                    $cloned->setStatus(Pairing::EXPRESS_STATUS_PAID);
                    $this->getRepository()->markAsPaid($pairing->getId());
                    $order = wc_get_order($pairing->getWcOrderId());
                    if ($order && $order->is_paid()) {
                        $this->getRepository()->markOrderSettled($pairing->getId());
                    }
                } catch (PaymentException $e) {
                    // Real decline (e.g. insufficient balance).
                    $this->logger->error('TWINT MonitorService::monitor: ' . $e->getMessage(), [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]);

                    $cloned->setStatus(Pairing::EXPRESS_STATUS_FAILED);
                    $this->getRepository()->markAsFailed($pairing->getId());
                } catch (Throwable $e) {
                    // Inconclusive (duplicate start / network): don't fail, keep polling.
                    $this->logger->warning(
                        "TWINT MonitorService::monitor: EC {$pairing->getId()} capture inconclusive " . $e->getMessage(),
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );

                    $fresh = $this->getRepository()->get($pairing->getId());
                    if ($fresh instanceof Pairing && ($fresh->isFinished() || $fresh->isSuccessful())) {
                        return MonitoringStatus::fromPairing($fresh);
                    }

                    // Timeout backstop: settle instead of hanging — but mark paid, not
                    // failed, if the capture already went through on a sub-order.
                    if ($pairing->isTimedOut()) {
                        $captured = false;
                        foreach ($this->getRepository()->findByWooOrderId($pairing->getWcOrderId()) as $sub) {
                            if (!$sub->getIsExpress() && $sub->isSuccessful()) {
                                $captured = true;
                                break;
                            }
                        }

                        if ($captured) {
                            $this->logger->info(
                                "TWINT MonitorService::monitor: EC {$pairing->getId()} timed out but capture succeeded, mark as paid",
                                [
                                    'source' => 'twint-woocommerce-extension',
                                    'wc_order_id' => $pairing->getWcOrderId(),
                                ]
                            );

                            $cloned->setStatus(Pairing::EXPRESS_STATUS_PAID);
                            $this->getRepository()->markAsPaid($pairing->getId());
                        } else {
                            $this->logger->error(
                                "TWINT MonitorService::monitor: EC {$pairing->getId()} capture timed out, mark as failed",
                                [
                                    'source' => 'twint-woocommerce-extension',
                                    'wc_order_id' => $pairing->getWcOrderId(),
                                ]
                            );

                            $cloned->setStatus(Pairing::EXPRESS_STATUS_FAILED);
                            $this->getRepository()->markAsFailed($pairing->getId());
                        }
                    } else {
                        return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                    }
                } finally {
                    $this->getRepository()->releaseConfirmLock($pairing->getId());
                }
            }

            return MonitoringStatus::fromPairing($cloned);
        }

        return $this->monitorRegular($pairing, $cloned);
    }

    /**
     * @throws Throwable
     */
    public function monitorExpress(Pairing $pairing, Pairing $cloned): MonitoringStatus
    {
        $client = $this->getBuilder()->build(Version::NEXT);

        $res = $this->getApi()->call(
            $client,
            'monitorFastCheckOutCheckIn',
            [PairingUuid::fromString($cloned->getId())],
            static fn (TransactionLog $log) => $log,
            false
        );

        return $this->monitorExpressRecursive($pairing, $cloned, $res, $client);
    }

    /**
     * @throws Throwable
     */
    public function monitorExpressRecursive(
        Pairing                   $pairing,
        Pairing                   $cloned,
        ApiResponse               $res,
        InvocationRecordingClient $client
    ): MonitoringStatus {
        /** @var FastCheckoutCheckIn $state */
        $state = $res->getReturn();

        $status = MonitoringStatus::STATUS_IN_PROGRESS;
        $finished = false;

        $diffs = $cloned->hasDiffs($state);
        $this->logger->info(
            "TWINT MonitorService::monitorExpressRecursive: EC {$pairing->getId()} {$cloned->getStatus()} {$cloned->getShippingMethod()}: diff: " . ($diffs ? 1 : 0),
            [
                'source' => 'twint-woocommerce-extension',
                'wc_order_id' => $pairing->getWcOrderId(),
            ]
        );

        if (!$diffs) {
            // Because cancelFastCheckoutCheckIn API return void then need monitor in next loop
            if ($state->pairingStatus()->__toString() === PairingStatus::PAIRING_IN_PROGRESS && $pairing->isTimedOut()) {
                $this->logger->info(
                    "TWINT MonitorService::monitorExpressRecursive: EC {$pairing->getId()} no diff, cancel it",
                    [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]
                );

                $cancellationRes = $this->cancelFastCheckoutCheckIn($cloned, $client);
                $log = $cancellationRes->getLog();
                $this->getLogRepository()->updatePartial($log, [
                    'pairing_id' => $cloned->getId(),
                    'order_id' => $cloned->getWcOrderId(),
                ]);

                $this->getRepository()->markAsMerchantCancelled($cloned->getId());
                $cloned->setStatus(Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED);
            }

            return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
        }

        try {
            $cloned = $this->getPairingService()->updateForExpress($cloned, $state);
            $this->logger->info("TWINT MonitorService::monitorExpressRecursive: EC {$pairing->getId()} was updated", [
                'source' => 'twint-woocommerce-extension',
                'wc_order_id' => $pairing->getWcOrderId(),
            ]);
        } catch (DatabaseException $e) {
            if ($e->getMessage() === TwintConstant::EXCEPTION_VERSION_CONFLICT) {
                $this->logger->info(
                    "TWINT MonitorService::monitorExpressRecursive: {$pairing->getId()} " . $e->getMessage(),
                    [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]
                );


                return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
            }

            throw $e;
        }

        $log = $res->getLog();
        $log->setOrderId($pairing->getWcOrderId());
        $log->setPairingId($pairing->getId());
        if ($log->isNewRecord()) {
            $this->getLogRepository()->insert($log);
        } else {
            $this->getLogRepository()->updatePartial($log, [
                'pairing_id' => $pairing->getId(),
                'order_id' => $pairing->getWcOrderId(),
            ]);
        }

        // As paid
        if ($pairing->getCustomerData() === [] && $state->hasCustomerData()) {
            $this->logger->info(
                "TWINT MonitorService::monitorExpressRecursive: EC paid {$pairing->getPairingStatus()} - {$cloned->getPairingStatus()}",
                [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]
            );

            $status = MonitoringStatus::STATUS_PAID;

            return MonitoringStatus::fromValues(true, $status, [
                'pairing' => $cloned,
            ]);
        }

        // As cancelled
        if (!$pairing->getIsOrdering() && $pairing->getPairingStatus() !== PairingStatus::NO_PAIRING && $cloned->getPairingStatus() === PairingStatus::NO_PAIRING && !$state->hasCustomerData()) {
            $this->logger->info(
                "TWINT MonitorService::monitorExpressRecursive: EC mark as cancelled {$pairing->getPairingStatus()} - {$cloned->getPairingStatus()}",
                [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]
            );

            $this->getRepository()->markAsCancelled($pairing->getId());
            $finished = true;
            $status = MonitoringStatus::STATUS_CANCELLED;
        }

        return MonitoringStatus::fromValues($finished, $status);
    }

    /**
     * @throws Throwable
     */
    public function cancelFastCheckoutCheckIn(Pairing $pairing, InvocationRecordingClient $client): ApiResponse
    {
        $this->logger->info("TWINT MonitorService::cancelFastCheckoutCheckIn: cancel EC {$pairing->getId()}", [
            'source' => 'twint-woocommerce-extension',
            'wc_order_id' => $pairing->getWcOrderId(),
        ]);


        return $this->getApi()->call($client, 'cancelFastCheckoutCheckIn', [
            PairingUuid::fromString($pairing->getId()),
        ], static function (TransactionLog $log) use ($pairing) {
            $log->setPairingId($pairing->getId());
            $log->setOrderId($pairing->getWcOrderId());

            return $log;
        }, true);
    }

    /**
     * @throws Throwable
     */
    public function monitorRegular(Pairing $pairing, Pairing $cloned): MonitoringStatus
    {
        $client = $this->getBuilder()->build();

        try {
            $res = $this->getApi()->call(
                $client,
                'monitorOrder',
                [new OrderId(new Uuid($pairing->getId()))],
                static fn (TransactionLog $log) => $log,
                false
            );
        } catch (Throwable $e) {
            $this->logger->error(
                'TWINT MonitorService::monitorRegular: cannot get pairing status ' . $e->getMessage(),
                [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]
            );

            throw $e;
        }

        return $this->recursiveMonitor($pairing, $cloned, $client, $res);
    }

    /**
     * @throws Throwable
     */
    protected function recursiveMonitor(
        Pairing                   $orgPairing,
        Pairing                   $pairing,
        InvocationRecordingClient $client,
        ApiResponse               $res
    ): MonitoringStatus {
        /** @var Order $tOrder */
        $tOrder = $res->getReturn();

        $hasDiff = $pairing->hasDiffs($tOrder);
        $this->logger->info(
            "TWINT MonitorService::recursiveMonitor: {$pairing->getId()} {$pairing->getStatus()} {$pairing->getTransactionStatus()} diff: " . ($hasDiff ? 1 : 0),
            [
                'source' => 'twint-woocommerce-extension',
                'wc_order_id' => $pairing->getWcOrderId(),
            ]
        );

        if ($hasDiff) {
            try {
                $pairing = $this->getPairingService()->update($pairing, $res);
                $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} was updated", [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]);
            } catch (DatabaseException $e) {
                if ($e->getMessage() === TwintConstant::EXCEPTION_VERSION_CONFLICT) {
                    $this->logger->info(
                        "TWINT MonitorService::recursiveMonitor: {$pairing->getId()} " . $e->getMessage(),
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );


                    return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                }

                throw $e;
            }

            $log = $res->getLog();
            $log->setPairingId($pairing->getId());
            $log->setOrderId($pairing->getWcOrderId());
            $this->getLogRepository()->save($log);
        }

        if ($tOrder->isPending()) {
            $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} still pending", [
                'source' => 'twint-woocommerce-extension',
                'wc_order_id' => $pairing->getWcOrderId(),
            ]);

            if ($tOrder->isConfirmationPending()) {
                $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} need confirm", [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]);


                // Grace window: if a confirm for this pairing was dispatched very
                // recently, do NOT hit TWINT again. The fast polls in between only
                // READ status (monitorOrder); if the capture went through we will see
                // SUCCESS. Only after the window may confirm be retried once more.
                $inflightKey = 'twint_confirm_inflight_' . $pairing->getId();
                if (get_transient($inflightKey)) {
                    $this->logger->info(
                        "TWINT MonitorService::recursiveMonitor: {$pairing->getId()} confirm in-flight, waiting",
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );

                    return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                }

                // Concurrency guard: only one worker (HTTP poll vs cron/CLI) may
                // confirm a given pairing at a time. Non-blocking; the loser backs
                // off immediately instead of issuing a duplicate confirm.
                if (!$this->getRepository()->acquireConfirmLock($pairing->getId())) {
                    return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                }

                try {
                    set_transient($inflightKey, time(), self::CONFIRM_GRACE_SECONDS);

                    $confirmRes = $this->getApi()->call($client, 'confirmOrder', [
                        new UnfiledMerchantTransactionReference((string) $pairing->getRefId()),
                        new Money(Money::CHF, $pairing->getAmount()),
                    ], static function (TransactionLog $log) use ($pairing) {
                        $log->setOrderId($pairing->getWcOrderId());
                        $log->setPairingId($pairing->getId());

                        return $log;
                    }, true);

                    // Definitive response received: clear the marker so the settle
                    // step can proceed without waiting out the grace window.
                    delete_transient($inflightKey);

                    $this->logger->info(
                        "TWINT MonitorService::recursiveMonitor: {$pairing->getId()} has been confirmed",
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );
                } catch (SdkError $e) {
                    // Timeout / API rejection / IO: the capture is INCONCLUSIVE - it
                    // may actually have succeeded. Keep the in-flight marker so the
                    // fast polls do NOT re-issue confirm; they only read status and
                    // settle to PAID once TWINT reflects it. Do NOT markAsFailed and
                    // do NOT throw (would fall through to the unassigned $confirmRes).
                    $this->logger->warning(
                        "TWINT MonitorService::recursiveMonitor: {$pairing->getId()} confirm inconclusive " . $e->getMessage(),
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );

                    return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                } catch (Throwable $e) {
                    // Non-SDK error = an unexpected bug on our side (DB, type error...).
                    // Clear the marker, do NOT markAsFailed, log and rethrow so it stays
                    // visible (the endpoint turns it into a soft IN_PROGRESS response).
                    delete_transient($inflightKey);
                    $this->logger->error(
                        "TWINT MonitorService::recursiveMonitor: {$pairing->getId()} confirm unexpected error " . $e->getMessage(),
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );

                    throw $e;
                } finally {
                    $this->getRepository()->releaseConfirmLock($pairing->getId());
                }

                return $this->recursiveMonitor($orgPairing, $pairing, $client, $confirmRes);
            }

            if ($orgPairing->isTimedOut()) {
                $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} was timed out", [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]);

                $cancellationRes = $this->getPairingService()->cancelOrder($pairing, $client);

                return $this->recursiveMonitor($orgPairing, $pairing, $client, $cancellationRes);
            }

            return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
        }

        if ($tOrder->isSuccessful() && !$pairing->isCaptured()) {
            $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} paid", [
                'source' => 'twint-woocommerce-extension',
                'wc_order_id' => $pairing->getWcOrderId(),
            ]);

            $this->settleOrder($pairing);

            return MonitoringStatus::fromValues(true, MonitoringStatus::STATUS_PAID);
        }

        if ($tOrder->isFailure() && !$orgPairing->isFailure()) {
            $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} failed", [
                'source' => 'twint-woocommerce-extension',
                'wc_order_id' => $pairing->getWcOrderId(),
            ]);

            return MonitoringStatus::fromValues(true, MonitoringStatus::STATUS_CANCELLED);
        }

        $this->logger->info("TWINT MonitorService::recursiveMonitor: {$pairing->getId()} still in process", [
            'source' => 'twint-woocommerce-extension',
            'wc_order_id' => $pairing->getWcOrderId(),
        ]);


        return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
    }

    /**
     * Idempotent order settlement. Safe to call multiple times.
     *
     * @param Pairing $pairing The pairing that triggered settlement.
     * @param string|null $transactionId Override transaction ID. For Express
     *                                   reconciliation, pass the sub-order's ID
     *                                   (not the EC pairing's UUID).
     */
    private function settleOrder(Pairing $pairing, ?string $transactionId = null): bool
    {
        $txId = $transactionId ?? $pairing->getId();
        $lock = PairingRepository::scopedLockName('stl_' . $pairing->getWcOrderId());
        if (!$this->getRepository()->acquireLock($lock)) {
            return false;
        }

        try {
            // Re-read order inside lock for current state
            $order = wc_get_order($pairing->getWcOrderId());
            if (!$order || $order->is_paid()) {
                // Already settled — mark so sweep skips next time
                $this->getRepository()->markOrderSettled($pairing->getId());
                return true;
            }

            // Settle whitelist: aligned with WooCommerce's
            // woocommerce_valid_order_statuses_for_payment_complete default
            // (on-hold, pending, failed, cancelled) plus the plugin's custom status,
            // plus 'checkout-draft' (Express orders stay in draft until capture).
            // Draft is not valid for payment_complete(), so it is moved to
            // 'pending' below before completing.
            $raw = RegularCheckoutGateway::getOrderStatusAfterFirstTimeCreatedOrder();
            $customStatus = str_starts_with($raw, 'wc-') ? substr($raw, 3) : $raw;
            $settleable = array_unique([
                'checkout-draft',
                'pending',
                $customStatus,
                'failed',
                'on-hold',
                'cancelled',
            ]);

            if (!$order->has_status($settleable)) {
                // Status outside whitelist (e.g. refunded, trash, custom).
                $this->getRepository()->markOrderSettled(
                    $pairing->getId(),
                    TwintConstant::ORDER_SETTLED_NEEDS_REVIEW
                );
                $order->add_order_note(sprintf(
                    'TWINT: payment was captured but order is in status "%s". Manual review required.',
                    $order->get_status()
                ));
                $this->logger->error(
                    "TWINT settleOrder: order {$pairing->getWcOrderId()} in status "
                    . "'{$order->get_status()}' — payment captured, needs manual review",
                    [
                        'source' => 'twint-woocommerce-extension',
                    ]
                );
                delete_transient('twint_needs_review_count');
                return false;
            }

            // Cancelled with refund → do NOT auto-complete.
            if ($order->has_status('cancelled') && $order->get_total_refunded() > 0) {
                $this->getRepository()->markOrderSettled(
                    $pairing->getId(),
                    TwintConstant::ORDER_SETTLED_NEEDS_REVIEW
                );
                $order->add_order_note(
                    'TWINT: payment was captured but order was cancelled and has a refund. Manual review required.'
                );
                $this->logger->error(
                    "TWINT settleOrder: order {$pairing->getWcOrderId()} cancelled with refund — needs manual review",
                    [
                        'source' => 'twint-woocommerce-extension',
                    ]
                );
                delete_transient('twint_needs_review_count');
                return false;
            }

            // Track cancelled orders being reopened
            $wasReopened = $order->has_status('cancelled');

            // Transition to 'pending' if current status is not in the list
            // that payment_complete() accepts. Read via the same filter so it
            // stays in sync with WooCommerce and third-party plugins.
            $originalStatus = $order->get_status();
            $validForComplete = apply_filters(
                'woocommerce_valid_order_statuses_for_payment_complete',
                ['on-hold', 'pending', 'failed', 'cancelled'],
                $order
            );
            if (!$order->has_status($validForComplete)) {
                $order->set_status('pending');
                $order->save();
            }

            $order->set_transaction_id($txId);
            $order->payment_complete($txId);

            // Defensive: verify the order actually transitioned to paid.
            $order = wc_get_order($pairing->getWcOrderId());
            if (!$order) {
                return false;
            }
            if (!$order->is_paid()) {
                // Mark NEEDS_REVIEW to stop sweep retrying every minute (N1 fix).
                $this->getRepository()->markOrderSettled(
                    $pairing->getId(),
                    TwintConstant::ORDER_SETTLED_NEEDS_REVIEW
                );
                $order->add_order_note(sprintf(
                    'TWINT: payment_complete() failed. Order was "%s"%s. Manual review required.',
                    $originalStatus,
                    $originalStatus !== $order->get_status() ? ' (transitioned to pending)' : ''
                ));
                $this->logger->error(
                    "TWINT settleOrder: order {$pairing->getWcOrderId()} still unpaid "
                    . "after payment_complete(), was '{$originalStatus}'. Marked NEEDS_REVIEW.",
                    [
                        'source' => 'twint-woocommerce-extension',
                    ]
                );
                delete_transient('twint_needs_review_count');
                return false;
            }

            $order->add_order_note('TWINT Checkout: The order was marked as paid programmatically.');

            if ($wasReopened) {
                $this->getRepository()->markOrderSettled($pairing->getId(), TwintConstant::ORDER_SETTLED_REOPENED);
                $order->add_order_note(
                    'TWINT: this order was cancelled but has been reopened because TWINT captured the payment. Please check stock and fulfillment.'
                );
                $this->logger->error(
                    "TWINT settleOrder: order {$pairing->getWcOrderId()} reopened from cancelled — check stock",
                    [
                        'source' => 'twint-woocommerce-extension',
                    ]
                );
                delete_transient('twint_reopened_count');
            } else {
                $this->getRepository()->markOrderSettled($pairing->getId());
            }

            return true;
        } finally {
            $this->getRepository()->releaseLock($lock);
        }
    }

    private function settleOrphanedPairings(): void
    {
        // Regular checkout: SUCCESS pairings whose order may not be paid
        $regularPairings = $this->getRepository()->loadUnsettledRegularPairings();
        foreach ($regularPairings as $pairing) {
            try {
                $settled = $this->settleOrder($pairing);
                if ($settled) {
                    $this->logger->info(
                        "TWINT reconciliation: settled order {$pairing->getWcOrderId()} "
                        . "for regular pairing {$pairing->getId()}",
                        [
                            'source' => 'twint-woocommerce-extension',
                        ]
                    );
                }
            } catch (Throwable $e) {
                $this->logger->error(
                    "TWINT reconciliation: regular {$pairing->getId()} " . $e->getMessage(),
                    [
                        'source' => 'twint-woocommerce-extension',
                    ]
                );
            }
        }

        // Express checkout: PAID pairings (via timeout backstop) whose order may not be paid
        $expressPairings = $this->getRepository()->loadUnsettledExpressPairings();
        foreach ($expressPairings as $pairing) {
            try {
                $order = wc_get_order($pairing->getWcOrderId());
                if ($order && $order->is_paid()) {
                    $this->getRepository()->markOrderSettled($pairing->getId());
                    continue;
                }

                // Find the sub-order's transaction ID for Express settlement
                $subOrderId = null;
                foreach ($this->getRepository()->findByWooOrderId($pairing->getWcOrderId()) as $sub) {
                    if (!$sub->getIsExpress() && $sub->isSuccessful()) {
                        $subOrderId = $sub->getId();
                        break;
                    }
                }

                if ($subOrderId === null) {
                    $this->getRepository()->markOrderSettled(
                        $pairing->getId(),
                        TwintConstant::ORDER_SETTLED_SKIPPED
                    );
                    $this->logger->info(
                        "TWINT reconciliation: express {$pairing->getId()} has no successful sub-order, skipping",
                        [
                            'source' => 'twint-woocommerce-extension',
                        ]
                    );
                    continue;
                }

                $settled = $this->settleOrder($pairing, $subOrderId);
                if ($settled) {
                    $this->logger->info(
                        "TWINT reconciliation: settled order {$pairing->getWcOrderId()} "
                        . "for express pairing {$pairing->getId()}",
                        [
                            'source' => 'twint-woocommerce-extension',
                        ]
                    );
                }
            } catch (Throwable $e) {
                $this->logger->error(
                    "TWINT reconciliation: express {$pairing->getId()} " . $e->getMessage(),
                    [
                        'source' => 'twint-woocommerce-extension',
                    ]
                );
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function status(Pairing $pairing): MonitoringStatus
    {
        if ($pairing->isFinished()) {
            return MonitoringStatus::fromPairing($pairing);
        }

        if (!$pairing->isMonitoring() && function_exists('shell_exec')) {
            try {
                $logFile = escapeshellarg(sys_get_temp_dir() . "/{$pairing->getId()}.log");
                $id = escapeshellarg($pairing->getId());

                // Check if WP-CLI is available
                if (shell_exec('wp --info') !== null) {
                    // Use WP-CLI if available
                    $shellCommand = "wp twint-poll poll {$id} --allow-root > {$logFile} 2>&1 &";

                    $this->logger->info("TWINT MonitorService::status: [WP-CLI] polling {$id}", [
                        'source' => 'twint-woocommerce-extension',
                    ]);
                } else {
                    // Fallback to PHP command if WP-CLI is not available
                    $phpExecutable = apply_filters('twint_poll_php_executable', Plugin::php());
                    $command = escapeshellarg(Plugin::abspath() . 'bin/console');
                    $statement = escapeshellarg(PollCommand::COMMAND);
                    $shellCommand = "{$phpExecutable} {$command} {$statement} {$id} > {$logFile} 2>&1 &";

                    $this->logger->info(
                        "TWINT MonitorService::status: [PHP-CLI] polling (WP-CLI not available) {$id}",
                        [
                            'source' => 'twint-woocommerce-extension',
                        ]
                    );
                }

                shell_exec($shellCommand);
            } catch (Throwable $e) {
                $this->logger->error('TWINT MonitorService::status: error start monitor: ' . $e->getMessage(), [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]);

                throw $e;
            }
        }

        return MonitoringStatus::fromPairing($pairing);
    }

    public function cancelRemainingPairings(int $orderId, InvocationRecordingClient $client): bool
    {
        $pairings = $this->getRepository()->findByWooOrderId($orderId);

        $settledAsPaid = false;

        /** @var Pairing $pairing */
        foreach ($pairings as $pairing) {
            if ($pairing->isFinished() || $pairing->getStatus() === Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED) {
                // Already settled before this call; the order is paid if this attempt succeeded.
                $settledAsPaid = $settledAsPaid || $pairing->isSuccessful();

                continue;
            }

            // Capture-first: settle an already-confirmed attempt instead of cancelling it.
            try {
                $status = $this->monitor($pairing);
                if ($status->paid()) {
                    $settledAsPaid = true;
                    $this->logger->info(
                        "TWINT MonitorService::cancelRemainingPairings: {$pairing->getId()} captured, skip cancel",
                        [
                            'source' => 'twint-woocommerce-extension',
                            'wc_order_id' => $pairing->getWcOrderId(),
                        ]
                    );

                    continue;
                }

                if ($status->finished()) {
                    // Settled as cancelled/failed during monitoring: nothing left to cancel.
                    continue;
                }
            } catch (Throwable $e) {
                // Monitoring failed; fall through to cancellation to preserve previous behaviour.
                $this->logger->error(
                    "TWINT MonitorService::cancelRemainingPairings: monitor failed {$pairing->getId()} {$e->getMessage()}",
                    [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]
                );
            }

            // Re-read the latest persisted state: monitoring may have captured/settled it.
            $fresh = $this->getRepository()->get($pairing->getId());
            if ($fresh instanceof Pairing
                && ($fresh->isFinished()
                    || $fresh->isSuccessful()
                    || $fresh->isCaptured()
                    || $fresh->getStatus() === Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED)
            ) {
                $settledAsPaid = $settledAsPaid || $fresh->isSuccessful() || $fresh->isCaptured();

                continue;
            }

            $toCancel = $fresh instanceof Pairing ? $fresh : $pairing;

            $res = $this->getPairingService()->cancelOrder($toCancel, $client);
            $this->getRepository()->markAsMerchantCancelled($toCancel->getId());

            $log = $res->getLog();
            $log->setPairingId($toCancel->getId());
            $log->setOrderId($toCancel->getWcOrderId());
            $this->getLogRepository()->save($log);
        }

        return $settledAsPaid;
    }

    public function cancel(Pairing $pairing): bool
    {
        $client = $this->getBuilder()->build(Version::NEXT);
        if ($pairing->getIsExpress()) {
            try {
                $result = $this->cancelFastCheckoutCheckIn($pairing, $client);
                $pairing->setStatus(Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED);
                $this->getRepository()->markAsMerchantCancelled($pairing->getId());
            } catch (CancellationFailed $e) {
                $this->logger->error(
                    "TWINT MonitorService::cancel: cancel checkin failed {$pairing->getId()}" . $e->getMessage(),
                    [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $pairing->getWcOrderId(),
                    ]
                );

                return false;
            } catch (Throwable $e) {
                return false;
            }
        } else {
            try {
                $monitorResponse = $this->monitor($pairing);

                if ($monitorResponse->finished()) {
                    return false;
                }

                $res = $this->getPairingService()->cancelOrder($pairing, $client);
                /** @var Order $order */
                $order = $res->getReturn();

                $this->getPairingService()->update($pairing, $res);

                return $order->transactionStatus()->equals(TransactionStatus::MERCHANT_ABORT());
            } catch (Throwable $e) {
                $this->logger->error("TWINT MonitorService::cancel: {$pairing->getId()}" . $e->getMessage(), [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]);

                return false;
            }
        }

        return true;
    }
}
