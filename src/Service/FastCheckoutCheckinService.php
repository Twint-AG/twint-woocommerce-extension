<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use AllowDynamicProperties;
use Throwable;
use Twint\Sdk\Value\CustomerDataScopes;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\ShippingMethod;
use Twint\Sdk\Value\ShippingMethodId;
use Twint\Sdk\Value\ShippingMethods;
use Twint\Sdk\Value\Version;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Pairing;
use WC_Logger_Interface;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Shipping_Rate;

/**
 * @method ClientBuilder getBuilder()
 * @method PairingService getPairingService()
 */
#[AllowDynamicProperties]
class FastCheckoutCheckinService
{
    use CartTrait;
    use LazyLoadTrait;

    protected static array $lazyLoads = ['builder', 'pairingService'];

    public function __construct(
        private readonly WC_Logger_Interface $logger,
        private Lazy|ClientBuilder           $builder,
        private readonly ApiService          $api,
        private Lazy|PairingService          $pairingService,
    ) {
        $this->getCartController();
    }

    public static function registerHooks(): void
    {
        add_filter('woocommerce_cart_shipping_packages', [self::class, 'forceCountry']);
    }

    /**
     * Express checkout only support for CH country
     * @param mixed $packages
     */
    public static function forceCountry($packages): array
    {
        foreach ($packages as &$package) {
            $package['destination']['country'] = 'CH';
        }

        return $packages;
    }

    /**
     * @throws Throwable
     */
    public function checkin(WC_Order $order): Pairing
    {
        $options = $this->getShippingOptions($order);
        $res = $this->callApi($order, $options);

        return $this->getPairingService()->createExpressPairing($res, $order);
    }

    protected function getShippingOptions(WC_Order $order): ShippingMethods
    {
        $order->remove_order_items('shipping');
        $order->calculate_totals();
        $base = $order->get_total();

        $options = [];

        foreach (ExpressCheckoutService::$packages as $package) {
            /** @var WC_Shipping_Rate $rate */
            foreach ($package['rates'] as $rate) {
                $order->remove_order_items('shipping');
                $item = new WC_Order_Item_Shipping();
                $item->set_method_id($rate->get_method_id()); // The shipping method ID (e.g., 'flat_rate')
                $item->set_total($rate->get_cost());

                $order->add_item($item);
                $order->calculate_totals();

                $options[] = new ShippingMethod(
                    new ShippingMethodId($rate->get_method_id()),
                    $rate->get_label(),
                    Money::CHF((float) wc_format_decimal(max($order->get_total() - $base, 0)))
                );
            }
        }

        $order->remove_order_items('shipping');
        $order->calculate_totals();
        $order->save();

        return new ShippingMethods(...$options);
    }

    /**
     * @throws Throwable
     */
    private function callApi(WC_Order $order, ShippingMethods $methods): ApiResponse
    {
        $client = $this->getBuilder()->build(Version::NEXT);

        $this->logger->info("TWINT start EC {$order->get_id()}");

        return $this->api->call(
            $client,
            'requestFastCheckOutCheckIn',
            [
                Money::CHF((float) $order->get_total()),
                new CustomerDataScopes(...CustomerDataScopes::all()),
                $methods,
            ]
        );
    }
}
