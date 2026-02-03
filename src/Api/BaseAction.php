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
     * This is a public endpoint, so we don't care about the nonce.
     * However, we can't change the permission_callback to return true,
     * because that happens AFTER the nonce check.
     * So we interfere here.
     */
    protected function allowPublicAccessIfRouteMatches(string $targetRoute): void
    {
        add_filter('rest_authentication_errors', static function ($result) use ($targetRoute) {
            if ($result === true || (is_wp_error(
                $result
            ) && $result->get_error_code() === 'rest_cookie_invalid_nonce')) {
                $currentRoute = $GLOBALS['wp']->query_vars['rest_route'] ?? null;
                if ($currentRoute === $targetRoute) {
                    return true;
                }
            }

            return $result;
        }, 101);
    }
}
