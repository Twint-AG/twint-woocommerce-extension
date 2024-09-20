<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use mysqli_result;
use mysqli_sql_exception;
use Throwable;
use Twint\Sdk\Value\InteractiveFastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Provider\DatabaseServiceProvider;
use WC_Logger_Interface;
use WC_Order;

class PairingService
{
    public function __construct(
        private readonly ClientBuilder $builder,
        private readonly ApiService $apiService,
        private readonly WC_Logger_Interface $logger
    ) {
    }

    public function create(ApiResponse $response, WC_Order $order): Pairing
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();

        $pairing = new Pairing();

        $pairing->setToken($tOrder->pairingToken()?->__toString());
        $pairing->setAmount($tOrder->amount()->amount());
        $pairing->setWcOrderId($order->get_id());
        $pairing->setId($tOrder->id()->__toString());
        $pairing->setTransactionStatus($tOrder->transactionStatus()->__toString());
        $pairing->setPairingStatus($tOrder->pairingStatus()->__toString());
        $pairing->setStatus($tOrder->status()->__toString());

        $pairing->save();

        return $pairing;
    }

    /**
     * @throws Throwable
     */
    public function monitor(Pairing $pairing): bool
    {
        if ($pairing->isFinished()) {
            return true;
        }

        $org = clone $pairing;

        $client = $this->builder->build();
        $apiResponse = $this->apiService->call($client, 'monitorOrder', [
            new OrderId(new Uuid($pairing->getId())),
        ], false);

        /** @var Order $tOrder */
        $tOrder = $apiResponse->getReturn();
        if ($pairing->getStatus() !== $tOrder->status()->__toString() ||
            $pairing->getPairingStatus() !== $tOrder->pairingStatus()?->__toString() ||
            $pairing->getTransactionStatus() !== $tOrder->transactionStatus()
                ->__toString()
        ) {
            try {
                $this->update($pairing, $apiResponse);
            } catch (mysqli_sql_exception $e) {
                if ($e->getSQLState() !== DatabaseServiceProvider::DATABASE_TRIGGER_STATE_ERROR_CODE) {
                    throw $e;
                }

                $this->logger->info(
                    "[TWINT] - Update pairing is locked {$pairing->getId()} {$pairing->getVersion()} {$pairing->getStatus()}"
                );

                return false;
            }
        }

        if ($tOrder->isPending()) {
            return false;
        }

        // Get WC Order
        $order = wc_get_order($pairing->getWcOrderId());

        if (!$org->isSuccess() && $tOrder->isSuccessful()) {
            $status = RegularCheckoutGateway::getOrderStatusAfterPaid();
            $this->logger->info("[TWINT] - Mark Order {$order->get_id()} and Pairing {$pairing->getId()} as {$status}");
            $order->update_status($status);
        }

        if (!$org->isFailed() && $tOrder->isFailure()) {
            $status = RegularCheckoutGateway::getOrderStatusAfterCancelled();
            $this->logger->info("[TWINT] - Mark Order {$order->get_id()} and Pairing {$pairing->getId()} as {$status}");
            $order->update_status($status);
        }

        $this->updateLog($apiResponse->getLog(), $pairing, $order);

        return true;
    }

    protected function update(Pairing $pairing, ApiResponse $response): int|bool|null|mysqli_result
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();
        global $wpdb;
        $newPairing = new Pairing();
        return $wpdb->update($newPairing->getTableName(), [
            'version' => $pairing->getVersion(),
            'status' => $tOrder->status()
                ->__toString(),
            'pairing_status' => $tOrder->pairingStatus()?->__toString(),
            'transaction_status' => $tOrder->transactionStatus()
                ->__toString(),
            'updated_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $pairing->getId(),
        ]);
    }

    /**
     * @throws Exception
     */
    protected function updateLog(array $log, Pairing $pairing, WC_Order $order): void
    {
        $log['pairing_id'] = $pairing->getId();
        $log['order_id'] = $order->get_id();
        $log['order_status'] = $order->get_status();
        $log['transaction_id'] = $order->get_transaction_id();

        $this->apiService->saveLog($log);
    }

    public function createExpressPairing(ApiResponse $response, WC_Order $order): Pairing{
        /** @var InteractiveFastCheckoutCheckIn $checkin */
        $checkin = $response->getReturn();

        $pairing = new Pairing();

        $pairing->setToken($checkin->pairingToken()?->__toString());
        $pairing->setAmount((float)$order->get_total());
        $pairing->setWcOrderId($order->get_id());
        $pairing->setId($checkin->pairingUuid()->__toString());
        $pairing->setPairingStatus($checkin->pairingStatus()->__toString());
        $pairing->setStatus(PairingStatus::PAIRING_IN_PROGRESS);
        $pairing->setIsExpress(true);

        $pairing->save();

        return $pairing;
    }
}
