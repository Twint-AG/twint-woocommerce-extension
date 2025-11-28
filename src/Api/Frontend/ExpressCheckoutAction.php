<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Throwable;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Service\ExpressCheckoutService;
use Twint\Woo\Service\MonitorService;
use WC_Data_Exception;
use WC_Logger_Interface;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @method MonitorService getMonitor()
 * @method ExpressCheckoutService getService()
 */
class ExpressCheckoutAction
{
    use LazyLoadTrait;
    use CartInitTrait;

    public const ROUTE = '/express/checkout';

    protected static array $lazyLoads = ['monitor', 'service'];

    public function __construct(
        private Lazy|ExpressCheckoutService  $service,
        private Lazy|MonitorService          $monitor,
        private readonly WC_Logger_Interface $logger
    ) {
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('twint/v1', self::ROUTE, [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /**
     * @throws WC_Data_Exception|Throwable
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $this->initCartIfNeed();

        $_REQUEST = array_merge($_REQUEST, $request->get_params());
        $full = $request->get_param('full') ?? false;

        if (!$full) {
            $empty = $this->getService()->isEmptyCart();
            $result = $this->getService()->addToCart($request);

            if (!$result['success']) {
                return new WP_REST_Response($result, 200);
            }

            if (!$empty) {
                return new WP_REST_Response([
                    'openMiniCart' => true,
                ], 200);
            }
        }

        try {
            $pairing = $this->getService()->checkout($full);
        } catch (Throwable $e) {
            $this->logger->error('TWINT Express Checkout error: ' . $e->getMessage(), $e->getTrace());
            return new WP_REST_Response([
                'success' => false,
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
                'message' => __('Error processing checkout. Please try again.', 'woocommerce'),
            ], 200);
        }

        $this->getMonitor()->status($pairing);

        return new WP_REST_Response([
            'pairing' => $pairing->getId(),
            'amount' => wc_price($pairing->getAmount()),
            'token' => $pairing->getToken(),
            'id' => $pairing->getWcOrderId(),
        ], 200);
    }
}
