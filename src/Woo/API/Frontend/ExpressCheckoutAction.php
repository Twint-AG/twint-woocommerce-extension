<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Throwable;
use Twint\Woo\Service\ExpressCheckoutService;
use WC_Data_Exception;
use WP_REST_Request;
use WP_REST_Response;

class ExpressCheckoutAction
{
    use CartInitTrait;

    public function __construct(
        private readonly ExpressCheckoutService $service
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
            $this->service->addToCart($request);

            if (!$this->service->isEmptyCart()) {
                return new WP_REST_Response([
                    'openMiniCart' => true,
                ], 200);
            }
        }

        $pairing = $this->service->checkout();

        return new WP_REST_Response([
            'pairing' => $pairing->getId(),
            'amount' => wc_price($pairing->getAmount()),
            'token' => $pairing->getToken(),
            'id' => $pairing->getWcOrderId(),
        ], 200);
    }
}
