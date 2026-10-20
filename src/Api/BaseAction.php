<?php

declare(strict_types=1);

namespace Twint\Woo\Api;

use Twint\Woo\Constant\TwintConstant;
use WP_REST_Request;
use WP_REST_Response;

abstract class BaseAction
{
    /**
     * Permission callback for TWINT REST routes.
     *
     * Initialises the WC session (not done automatically for REST requests)
     * then verifies the X-TWINT-Nonce header. Session init is required because
     * WC_Session_Handler binds nonces whose action starts with "woocommerce"
     * to the session's customer_id via nonce_user_logged_out filter.
     */
    public function verifyTwintNonce(WP_REST_Request $request): bool
    {
        $this->ensureWcSession();

        $nonce = $request->get_header(TwintConstant::NONCE_HEADER);

        return $nonce !== null && (bool) wp_verify_nonce($nonce, TwintConstant::NONCE_ACTION);
    }

    /**
     * Initialise WC session if not already done.
     * WC skips session init for REST requests (is_request('frontend') is false).
     */
    protected function ensureWcSession(): void
    {
        $wc = WC();
        if (!$wc->session) {
            $wc->initialize_session();
        }
    }

    /**
     * Register a rest_post_dispatch filter that attaches a fresh X-TWINT-Nonce
     * to every TWINT route response — including 401/403 error responses.
     *
     * rest_post_dispatch is used instead of rest_request_after_callbacks because
     * when permission_callback returns false, the response in
     * rest_request_after_callbacks is a WP_Error (not WP_REST_Response).
     * rest_post_dispatch fires after the error has been converted.
     *
     * Call ONCE from plugin init, not per-action constructor.
     */
    public static function registerNonceResponseFilter(): void
    {
        add_filter('rest_post_dispatch', static function ($response, $server, WP_REST_Request $request) {
            if (!$response instanceof WP_REST_Response) {
                return $response;
            }

            $route = $request->get_route();
            if (!str_starts_with($route, '/twint/v1/')) {
                return $response;
            }

            $wc = WC();
            if (!$wc->session) {
                $wc->initialize_session();
            }

            $response->header(TwintConstant::NONCE_HEADER, wp_create_nonce(TwintConstant::NONCE_ACTION));

            return $response;
        }, 10, 3);
    }
}
