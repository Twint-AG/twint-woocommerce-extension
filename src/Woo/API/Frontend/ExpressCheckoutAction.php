<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use WP_REST_Request;
use WP_REST_Response;

class ExpressCheckoutAction {
    public function __construct(

    )
    {
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('twint/v1', '/express/checkout', [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        sleep(2);

        return new WP_REST_Response(['success' => true], 200);
    }
}
