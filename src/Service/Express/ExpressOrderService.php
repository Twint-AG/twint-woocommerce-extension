<?php

declare(strict_types=1);

namespace Twint\Woo\Service\Express;

use Throwable;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Version;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Exception\PaymentException;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\MonitorService;
use Twint\Woo\Service\PairingService;
use WC_Logger_Interface;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Shipping_Rate;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * @method ClientBuilder getBuilder()
 * @method PairingRepository getPairingRepository()
 * @method PairingService getPairingService()
 * @method ApiService getApi()
 */
class ExpressOrderService
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['builder', 'pairingRepository', 'pairingService', 'api'];

    private MonitorService $monitor;

    public function __construct(
        private Lazy|PairingRepository       $pairingRepository,
        private Lazy|ApiService              $api,
        private readonly WC_Logger_Interface $logger,
        private Lazy|ClientBuilder           $builder,
        private Lazy|PairingService          $pairingService
    ) {
    }

    public function setMonitor(MonitorService $monitor): void
    {
        $this->monitor = $monitor;
    }

    /**
     * @throws Throwable
     */
    public function update(Pairing $pairing): void
    {
        $this->getPairingRepository()->markAsOrdering($pairing->getId());

        $order = wc_get_order($pairing->getWcOrderId());
        if (!$order) {
            $this->logger->error(
                "TWINT ExpressOrderService::update: order {$pairing->getWcOrderId()} not found (deleted draft?)",
                [
                    'source' => 'twint-woocommerce-extension',
                ]
            );

            throw new PaymentException("TWINT: order {$pairing->getWcOrderId()} no longer exists.");
        }

        $this->updateAddress($order, $pairing);
        $this->updateShippingMethod($order, $pairing);

        // Check for total mismatch vs check-in amount
        $snapshot = $order->get_meta(TwintConstant::META_SHIPPING_RATES);
        if (is_array($snapshot) && isset($snapshot['base_total'])) {
            $shippingMethodKey = $pairing->getShippingMethodId()
                . TwintConstant::SHIPPING_SEPARATOR
                . $pairing->getShippingMethodInstanceId();
            $rateData = $snapshot['rates'][$shippingMethodKey] ?? null;
            if ($rateData !== null) {
                $expectedTotal = (float) $snapshot['base_total'] + (float) $rateData['amount'];
                $actualTotal = (float) $order->get_total();
                if (abs($expectedTotal - $actualTotal) > 0.01 && !$order->get_meta('_twint_mismatch_noted')) {
                    $note = sprintf(
                        /* translators: %1$.2f: expected total, %2$.2f: actual total */
                        __('TWINT: Shipping/total differs from amount shown in TWINT app: expected CHF %1$.2f, actual CHF %2$.2f', 'twint-woocommerce-extension'),
                        $expectedTotal,
                        $actualTotal
                    );
                    $this->logger->warning("TWINT ExpressOrderService::update: {$note}", [
                        'source' => 'twint-woocommerce-extension',
                        'wc_order_id' => $order->get_id(),
                    ]);
                    $order->add_order_note($note);
                    $order->update_meta_data('_twint_mismatch_noted', '1');
                    $order->save();
                }
            }
        }

        $new = $this->startOrder($order, $pairing);

        $order->update_status('pending');
        $order->payment_complete($new->getId());
        $order->add_order_note('TWINT Express Checkout');

        @$this->cleanCart();
    }

    protected function updateAddress(WC_Order $order, Pairing $pairing): void
    {
        $data = $pairing->getCustomerData();

        // Define the new address data
        $address = [
            'first_name' => $data['shipping_address']['firstName'],
            'last_name' => $data['shipping_address']['lastName'],
            'company' => '',
            'address_1' => $data['shipping_address']['street'],
            'address_2' => '',
            'city' => $data['shipping_address']['city'],
            'state' => '',
            'postcode' => $data['shipping_address']['zip'],
            'country' => $data['shipping_address']['country'],
            'email' => $data['email'],
            'phone' => $data['phone_number'],
        ];

        $order->set_address($address, 'billing');
        $order->set_address($address, 'shipping');

        // Save the changes
        $order->save();
    }

    private function updateShippingMethod(WC_Order $order, Pairing $pairing): void
    {
        if (in_array($pairing->getShippingMethod(), [null, '', '0'], true)) {
            return;
        }

        [$methodTitle, $rate] = $this->getShippingInfo($pairing, $order);

        if (!($rate instanceof WC_Shipping_Rate)) {
            throw new PaymentException(sprintf(
                'TWINT: no valid shipping rate for method %s on order %d',
                $pairing->getShippingMethodId() . ':' . $pairing->getShippingMethodInstanceId(),
                $order->get_id()
            ));
        }

        $shippingItems = $order->get_items('shipping');
        /** @var WC_Order_Item_Shipping $item */
        $item = reset($shippingItems);

        if (!($item instanceof WC_Order_Item_Shipping)) {
            // If no shipping item exists, create a new one
            $item = new WC_Order_Item_Shipping();

            // Set the order ID for the new item
            $item->set_order_id($order->get_id());

            // Set method ID and method title
            $item->set_method_id($pairing->getShippingMethodId());
            $item->set_instance_id($pairing->getShippingMethodInstanceId());
            $item->set_method_title($methodTitle);
            $item->set_shipping_rate($rate);

            // Add the item to the order
            $order->add_item($item);
        } else {
            $item->set_method_id($pairing->getShippingMethodId());
            $item->set_instance_id($pairing->getShippingMethodInstanceId());
            $item->set_method_title($methodTitle);
            $item->set_shipping_rate($rate);
        }

        $item->save();

        // Recalculate totals after updating the shipping method
        $order->calculate_shipping();
        $order->calculate_totals();

        // Save the order
        $order->save();
    }

    /**
     * Returns [label, WC_Shipping_Rate|false|null] for the selected shipping method.
     *
     * Primary path: reconstruct WC_Shipping_Rate from the snapshot saved at check-in
     * (META_SHIPPING_RATES). This avoids calling calculate_shipping() in a background
     * context where WC()->cart is not initialised, which fatals with third-party plugins.
     *
     * Fallback path: when no snapshot exists (pairing created before upgrade) or the
     * selected rate key is absent from the snapshot, delegate to getShippingInfoLegacy()
     * which initialises the WC cart/session before calling calculate_shipping().
     */
    protected function getShippingInfo(Pairing $pairing, WC_Order $order): array
    {
        $snapshot = $order->get_meta(TwintConstant::META_SHIPPING_RATES);

        if (is_array($snapshot) && !empty($snapshot['rates'])) {
            $shippingMethodKey = $pairing->getShippingMethodId()
                . TwintConstant::SHIPPING_SEPARATOR
                . $pairing->getShippingMethodInstanceId();

            $rateData = $snapshot['rates'][$shippingMethodKey] ?? null;

            if ($rateData !== null) {
                return $this->buildRateFromSnapshot($rateData);
            }

            // Key not in snapshot — fall through to legacy recalculation
            $this->logger->warning(
                "TWINT ExpressOrderService::getShippingInfo: rate {$shippingMethodKey} not in snapshot for order {$order->get_id()}, falling back to recalculation",
                [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $order->get_id(),
                ]
            );
        } else {
            // No snapshot at all — pairing created before upgrade
            $this->logger->warning(
                "TWINT ExpressOrderService::getShippingInfo: no snapshot for order {$order->get_id()}, falling back to recalculation",
                [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $order->get_id(),
                ]
            );
        }

        return $this->getShippingInfoLegacy($pairing, $order);
    }

    private function buildRateFromSnapshot(array $rateData): array
    {
        $rateId = $rateData['method_id'] . ':' . $rateData['instance_id'];
        $rate = new WC_Shipping_Rate(
            $rateId,
            $rateData['label'],
            $rateData['cost'],
            $rateData['taxes'] ?? [],
            $rateData['method_id']
        );
        $rate->set_instance_id((int) $rateData['instance_id']);

        if (!empty($rateData['meta_data'])) {
            foreach ($rateData['meta_data'] as $key => $value) {
                $rate->add_meta_data($key, $value);
            }
        }

        return [$rateData['label'], $rate];
    }

    /**
     * Fallback for pairings without snapshot or missing key.
     * Initialises WC cart/session so shipping plugins that access WC()->cart don't fatal.
     *
     * Notes: bug in the Woo core of \WC_Shipping::load_shipping_methods method make the shipping calculation get wrong costs
     * This method need to apply workaround solution
     */
    private function getShippingInfoLegacy(Pairing $pairing, WC_Order $order): array
    {
        $wc = WC();
        if (!$wc->cart) {
            $wc->frontend_includes();
            $wc->initialize_session();
            $wc->initialize_cart();
        }

        $data = $pairing->getCustomerData();

        [$contents, $cost] = $this->buildPackageContents($order);

        $package = [
            'contents' => $contents,
            'contents_cost' => $cost,
            'applied_coupons' => $order->get_coupon_codes(),
            'user' => [
                'ID' => $order->get_user_id(),
            ],
            'destination' => [
                'country' => $data['shipping_address']['country'],
                'state' => '',
                'postcode' => $data['shipping_address']['zip'],
            ],
        ];

        $methods = [];
        $method = null;

        $zone = WC_Shipping_Zones::get_zone_matching_package($package);
        if ($zone instanceof WC_Shipping_Zone) {
            $rawMethods = $zone->get_shipping_methods(true);

            foreach ($rawMethods as $method) {
                $methods[$method->instance_id] = $method;
            }

            $method = $methods[$pairing->getShippingMethodInstanceId()] ?? null;
        }

        if (!$method) {
            $methods = WC()->shipping()->get_shipping_methods();
            $method = $methods[$pairing->getShippingMethodId()] ?? null;
        }

        if ($method) {
            $method->calculate_shipping($package);
            $rates = $method->rates;

            return [$method->get_method_title(), reset($rates)];
        }

        return [$pairing->getShippingMethodId(), null];
    }

    private function buildPackageContents(WC_Order $order): array
    {
        $items = [];
        $cost = 0;

        /** @var WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            if (!$item->is_type('line_item')) {
                continue;
            }

            /** @var WC_Product $product */
            $product = $item->get_product();
            if (!($product instanceof WC_Product)) {
                continue;
            }

            // Build package item data
            $items[$item->get_id()] = [
                'data' => $product,
                'quantity' => $item->get_quantity(),
                'line_total' => $item->get_total(),
                'line_tax' => $item->get_total_tax(),
                'line_subtotal' => $item->get_subtotal(),
                'line_subtotal_tax' => $item->get_subtotal_tax(),
            ];

            $cost += $item->get_total();
        }

        return [$items, $cost];
    }

    /**
     * @throws Throwable
     */
    private function startOrder(WC_Order $order, Pairing $pairing): Pairing
    {
        // Resume an existing sub-order rather than starting a new one, so a retry never charges twice.
        foreach ($this->getPairingRepository()->findByWooOrderId($order->get_id()) as $existing) {
            if ($existing->getIsExpress()) {
                continue;
            }
            if ($existing->isSuccessful()) {
                return $existing;
            }
            if (!$existing->isFinished()) {
                if ($this->monitorPairing($existing)) {
                    return $existing;
                }

                throw new PaymentException('TWINT: resumed fast-checkout order did not settle as paid.');
            }

            // A finished-but-unsuccessful sub-order is a genuine failure.
            throw new PaymentException('TWINT: previous fast-checkout order failed.');
        }

        $client = $this->getBuilder()->build(Version::NEXT);

        $refId = $order->get_id() . '-' . wp_generate_password(4, false);

        $res = $this->getApi()->call($client, 'startFastCheckoutOrder', [
            PairingUuid::fromString($pairing->getId()),
            new UnfiledMerchantTransactionReference($refId),
            new Money(TwintConstant::SUPPORTED_CURRENCY, (float) $order->get_total()),
        ], static function (TransactionLog $log) use ($order) {
            $log->setOrderId($order->get_id());

            return $log;
        }, true);

        $newPairing = $this->getPairingService()->create($res, $order, true);
        $this->logger->info("TWINT ExpressOrderService::startOrder: EC {$pairing->getId()} -> {$newPairing->getId()}");

        $success = $this->monitorPairing($newPairing);
        if (!$success) {
            throw new PaymentException('TWINT: Your balance is insufficient.');
        }

        return $newPairing;
    }

    /**
     * @throws Throwable
     */
    protected function monitorPairing(Pairing $pairing): bool
    {
        do {
            $status = $this->monitor->monitor($pairing);
            $pairing = $this->getPairingRepository()->get($pairing->getId());
        } while (!$status->finished());

        $this->logger->info("TWINT ExpressOrderService::monitorPairing: EC monitor finished {$pairing->getId()}");

        return $status->paid();
    }

    /**
     * Clean up cart if can
     */
    private function cleanCart(): void
    {
        // @phpstan-ignore-next-line
        WC()?->cart?->empty_cart();
    }
}
