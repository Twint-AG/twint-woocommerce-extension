<?php

namespace TWINT\Woo\CronJob;

use Twint\Woo\Services\SettingService;

class TwintCancelOrderExpiredCronJob
{
    public const HOOK_NAME = 'twint_cancel_expired_orders';

    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'wpCronSchedules']);

        add_action(self::HOOK_NAME, [$this, 'twintCancelExpiredOrders']);
    }

    public static function INIT_CRONJOB(): void
    {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'twint15minutes', self::HOOK_NAME);
        }
    }

    public static function REMOVE_CRONJOB(): void
    {
        wp_clear_scheduled_hook(self::HOOK_NAME);
    }

    public function wpCronSchedules($schedules)
    {
        if (!isset($schedules['twint15minutes'])) {
            $schedules['twint15minutes'] = [
                'interval' => 15 * 60,
                'display' => __('Once every 15 minutes'),
            ];
        }

        return $schedules;
    }

    public function twintCancelExpiredOrders(): void
    {
        wc_get_logger()->info(
            'twintCronJobRunning',
            [
                'Running twint cancel expired orders',
            ]
        );
        // Get pending orders within X minutes (configurable in admin setting)
        $onlyPickOrderFromMinutes = get_option(SettingService::MINUTES_PENDING_WAIT, 30);
        $time = strtotime("-{$onlyPickOrderFromMinutes} minutes");
        $time = date('Y-m-d H:i:s', $time);
        $pendingOrders = wc_get_orders([
            'type' => 'shop_order',
            'limit' => -1,
            'payment_method' => 'twint',
            'status' => [
                'wc-pending',
            ],
            'date_before' => $time,
        ]);

        foreach ($pendingOrders as $order) {
            $msgNote = __('The order has been cancelled due to expired after ' . $onlyPickOrderFromMinutes . ' minutes.', 'woocommerce-gateway-twint');
            $order->update_status('cancelled', $msgNote);
        }

        wc_get_logger()->info(
            'twintCronJobDone',
            [
                'There are ' . count($pendingOrders) . ' pending orders has run.',
            ]
        );
    }
}