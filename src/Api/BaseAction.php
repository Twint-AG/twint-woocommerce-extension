<?php

declare(strict_types=1);

namespace Twint\Woo\Api;

use Twint\Woo\Constant\TwintConstant;
use WP_REST_Request;
use WP_REST_Response;

abstract class BaseAction
{
    /**
     * Reject unauthenticated admin-ajax requests.
     * Used by wp_ajax_nopriv_* hooks in admin actions.
     */
    public function requireLogin(): void
    {
        wp_send_json_error(['message' => 'Authentication required.'], 401);
    }

    /**
     * Register response filters for TWINT REST routes.
     *
     * - Attaches a fresh X-TWINT-Nonce to every TWINT response (including errors).
     * - Allows X-TWINT-Nonce in CORS for trusted origins only.
     *
     * Call ONCE from plugin init, not per-action constructor.
     */
    public static function registerNonceResponseFilter(): void
    {
        // Allow X-TWINT-Nonce in cross-origin requests, but ONLY for trusted origins.
        // WordPress reflects any Origin with Access-Control-Allow-Credentials: true
        // (rest_send_cors_headers). Exposing X-TWINT-Nonce to arbitrary origins would
        // leak a CSRF token cross-site. Do NOT remove the is_allowed_http_origin() check.
        // Shops using WPML domain-per-language with REST on a different domain must add
        // their domains via the allowed_http_origins filter.
        $addNonceHeader = static function ($headers) {
            $headers = is_array($headers) ? $headers : [];
            if (!is_allowed_http_origin()) {
                return $headers;
            }
            $headers[] = TwintConstant::NONCE_HEADER;

            return array_values(array_unique($headers));
        };
        add_filter('rest_allowed_cors_headers', $addNonceHeader);
        add_filter('rest_exposed_cors_headers', $addNonceHeader);

        // Attach a fresh X-TWINT-Nonce to every TWINT route response.
        // rest_post_dispatch fires after WP_Error has been converted to WP_REST_Response,
        // so this covers 401/403 error responses too.
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
}
