<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Throwable;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Service\ExpressCheckoutService;
use Twint\Woo\Service\MonitorService;
use WC_Data_Exception;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @method MonitorService getMonitor()
 */
class ExpressCheckoutAction
{
    use LazyLoadTrait;
    use CartInitTrait;

    protected static array $lazyLoads = ['monitor'];

    public function __construct(
        private readonly ExpressCheckoutService $service,
        private readonly Lazy|MonitorService      $monitor,
    ) {
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('twint/v1', '/express/checkout', [
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

        $full = $request->get_param('full') ?? false;

        if (!$full) {
            $empty = $this->service->isEmptyCart();
            $result = $this->service->addToCart($request);

            if (!$result['success']) {
                return new WP_REST_Response($result, 200);
            }

            if (!$empty) {
                return new WP_REST_Response([
                    'openMiniCart' => true,
                ], 200);
            }
        }

        $pairing = $this->service->checkout($full);

        // Start monitoring in background
        if (get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes') {
            $this->getMonitor()->status($pairing);
        }

        return new WP_REST_Response([
            'pairing' => $pairing->getId(),
            'amount' => wc_price($pairing->getAmount()),
            'token' => $pairing->getToken(),
            'id' => $pairing->getWcOrderId(),
        ], 200);
    }
}
