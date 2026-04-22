<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use Twint\Sdk\Value\AlphanumericPairingToken;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Factory\ClientBuilder;
use function Psl\Type\string;

/**
 * @method ClientBuilder getBuilder()
 */
class AppsService
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['builder'];

    public function __construct(
        private Lazy|ClientBuilder $builder
    ) {
    }

    public function getPayLinks(string $token = '--TOKEN--'): array
    {
        $payLinks = [];
        try {
            $client = $this->getBuilder()->build();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $agent = empty($_SERVER['HTTP_USER_AGENT']) ?
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                '' : wp_unslash(sanitize_text_field($_SERVER['HTTP_USER_AGENT']));
            $device = $client->detectDevice(string()->assert($agent));
            $pairingToken = AlphanumericPairingToken::fromString($token);

            if ($device->isAndroid()) {
                $payLinks['android'] = (string) $client->getAndroidAppUrl($pairingToken);
            } elseif ($device->isIos()) {
                $appList = [];
                $apps = $client->getIosAppSchemes();
                foreach ($apps as $app) {
                    $appList[] = [
                        'name' => $app->displayName(),
                        'link' => (string) $client->getIosAppUrl($app, $pairingToken),
                    ];
                }
                $payLinks['ios'] = $appList;
            }
        } catch (Exception $e) {
            return $payLinks;
        }

        return $payLinks;
    }
}
