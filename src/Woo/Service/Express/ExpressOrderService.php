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
use WC_Order_Item_Shipping;
use WC_Shipping_Zones;

/**
 * @method ClientBuilder getBuilder()
 * @method PairingRepository getPairingRepository()
 * @method PairingService getPairingService()
 */
class ExpressOrderService
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['builder', 'pairingRepository', 'pairingService'];

    private MonitorService $monitor;

    public function __construct(
        private readonly Lazy|PairingRepository $pairingRepository,
        private readonly ApiService $api,
        private readonly WC_Logger_Interface $logger,
        private Lazy|ClientBuilder $builder,
        private Lazy|PairingService $pairingService,
        MonitorService $monitor = null
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
        $this->getPairingRepository()
            ->markAsOrdering($pairing->getId());

        $order = wc_get_order($pairing->getWcOrderId());

        $this->updateAddress($order, $pairing);
        $this->updateShippingMethod($order, $pairing);

        $this->startOrder($order, $pairing);

        $order->payment_complete();
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
        if ($pairing->getShippingMethodId() === null || $pairing->getShippingMethodId() === '' || $pairing->getShippingMethodId() === '0') {
            return;
        }

        list($methodTitle, $rate) = $this->getShippingInfo($pairing, $order);

        $shippingItems = $order->get_items('shipping');
        $item = reset($shippingItems);

        if (!$item) {
            // If no shipping item exists, create a new one
            $item = new WC_Order_Item_Shipping();

            // Set the order ID for the new item
            $item->set_order_id($order->get_id());

            // Set method ID and method title
            $item->set_method_id($pairing->getShippingMethodId());
            $item->set_method_title($methodTitle);
            $item->set_shipping_rate($rate);


            // Add the item to the order
            $order->add_item($item);


            // Save the item
        } else {
            $item->set_method_id($pairing->getShippingMethodId());
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
     * Notes: bug in the Woo core of \WC_Shipping::load_shipping_methods method make the shipping calculation get wrong costs
     * This method need to apply workaround solution
     */
    protected function getShippingInfo(Pairing $pairing, WC_Order $order): array
    {
        $data = $pairing->getCustomerData();

        list($contents, $cost) = $this->buildPackageContents($order);

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

        // Get all shipping methods
        $zone = WC_Shipping_Zones::get_zone_matching_package($package);
        if ($zone) {
            $rawMethods = $zone->get_shipping_methods(true);
            foreach ($rawMethods as $method) {
                $methods[$method->id] = $method;
            }
        } else {
            $methods = WC()
                ->shipping()
                ->get_shipping_methods();
        }

        $method = $methods[$pairing->getShippingMethodId()] ?? null;

        if ($method) {
            $rates = $method->get_rates_for_package($package);

            return [$method->get_method_title(), reset($rates)];
        }

        // If method not found, return the original method ID
        return [$pairing->getShippingMethodId(), null];
    }

    private function buildPackageContents(WC_Order $order): array
    {
        $items = [];
        $cost = 0;

        foreach ($order->get_items() as $item) {
            if (!$item->is_type('line_item')) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
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
    private function startOrder(WC_Order $order, Pairing $pairing): void
    {
        $client = $this->getBuilder()
            ->build(Version::NEXT);

        $res = $this->api->call($client, 'startFastCheckoutOrder', [
            PairingUuid::fromString($pairing->getId()),
            new UnfiledMerchantTransactionReference((string) $order->get_id()),
            new Money(TwintConstant::SUPPORTED_CURRENCY, (float) $order->get_total()),
        ], true, static function (TransactionLog $log) use ($order) {
            $log->setOrderId($order->get_id());

            return $log;
        });

        $newPairing = $this->getPairingService()
            ->create($res, $order, true);

        $success = $this->monitorPairing($newPairing);
        if (!$success) {
            throw new PaymentException('TWINT: Your balance is insufficient.');
        }
    }

    /**
     * @throws Throwable
     */
    protected function monitorPairing(Pairing $pairing): bool
    {
        do {
            $this->logger->info(
                "TWINT EC monitor: {$pairing->getId()} {$pairing->getStatus()} {$pairing->getTransactionStatus()} {$pairing->getPairingStatus()}"
            );

            $status = $this->monitor->monitor($pairing);
        } while (!$status->finished());

        return $status->paid();
    }

    public function cancelOrder(Pairing $pairing): void
    {
        $order = wc_get_order($pairing->getWcOrderId());
        $order->update_status('cancelled', 'Order cancelled programmatically.');

        // Optionally, you can add an order note
        $order->add_order_note('The order was cancelled via custom PHP code.');

        $order->save();
    }
}
