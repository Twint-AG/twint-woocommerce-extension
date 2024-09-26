<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
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
            $device = $client->detectDevice(string()->assert($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if ($device->isAndroid()) {
                $payLinks['android'] = 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code =' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end';
            } elseif ($device->isIos()) {
                $appList = [];
                $apps = $client->getIosAppSchemes();
                foreach ($apps as $app) {
                    $appList[] = [
                        'name' => $app->displayName(),
                        'link' => $app->scheme() . 'applinks/?al_applink_data={"app_action_type":"TWINT_PAYMENT","extras": {"code": "' . $token . '",},"referer_app_link": {"target_url": "", "url": "", "app_name": "EXTERNAL_WEB_BROWSER"}, "version": "6.0"}',
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
