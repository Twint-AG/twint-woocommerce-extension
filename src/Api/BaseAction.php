<?php

declare(strict_types=1);

namespace Twint\Woo\Api;

abstract class BaseAction
{
    public function requireLogin(): void
    {
        echo 'You must login to do next actions';
        die();
    }

    /**
     * Bypasses authentication errors for specific public endpoints.
     *
     * This interferes with `rest_authentication_errors` to allow public
     * access even if other auth plugins (e.g., Application Passwords)
     * return a WP_Error (like `invalid_username` or `rest_cookie_invalid_nonce`).
     *
     * Security: Safe because it strictly targets whitelisted TWINT routes.
     * Endpoints still validate the `pairingId` UUID against the database
     * before processing or returning any data.
     */
    protected function allowPublicAccessIfRouteMatches(string $targetRoute): void
    {
        add_filter('rest_authentication_errors', static function ($result) use ($targetRoute) {
            if ($result === true || is_wp_error($result)) {
                $currentRoute = $GLOBALS['wp']->query_vars['rest_route'] ?? null;
                if ($currentRoute === $targetRoute) {
                    return true;
                }
            }

            return $result;
        }, 101);
    }
}
