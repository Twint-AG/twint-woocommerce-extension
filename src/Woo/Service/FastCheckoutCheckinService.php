<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;
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
use WC_Shipping_Rate;

/**
 * @method ClientBuilder getBuilder()
 */
class FastCheckoutCheckinService
{
    use LazyLoadTrait;
    protected static array $lazyLoads = ['builder'];
    
    public function __construct(
        private readonly WC_Logger_Interface $logger,
        private Lazy | ClientBuilder $builder,
        private readonly ApiService $api,
        private readonly PairingService $pairingService,
    ) {
    }

    public static function registerHooks(): void
    {
        add_filter('woocommerce_cart_shipping_packages', [self::class, 'forceCountry']);
    }

    /**
     * Express checkout only support for CH country
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
        $options = $this->getShippingOptions();
        $res = $this->callApi($order, $options);

        return $this->pairingService->createExpressPairing($res, $order);
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

    protected function getShippingOptions(): ShippingMethods
    {
        $options = [];

        $controller = new CartController();
        $packages = $controller->get_shipping_packages();

        foreach ($packages as $package) {
            /** @var WC_Shipping_Rate $rate */
            foreach ($package['rates'] as $rate) {
                $options[] = new ShippingMethod(
                    new ShippingMethodId($rate->get_method_id()),
                    $rate->get_label(),
                    Money::CHF((float) $rate->get_cost())
                );
            }
        }

        return new ShippingMethods(...$options);
    }
}
