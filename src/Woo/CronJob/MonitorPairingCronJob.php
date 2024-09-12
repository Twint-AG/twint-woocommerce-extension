<?php

namespace Twint\Woo\CronJob;

use Twint\Woo\Service\SettingService;
use WC_Logger_Interface;

class MonitorPairingCronJob
{
    public const HOOK_NAME = 'twint_order_handler';

    public function __construct(
        private readonly WC_Logger_Interface $logger
    )
    {
        add_filter('cron_schedules', [$this, 'wpCronSchedules']);

        add_action(self::HOOK_NAME, [$this, 'run']);
    }

    public static function initCronjob(): void
    {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'twint1minute', self::HOOK_NAME);
        }
    }

    public static function removeCronjob(): void
    {
        wp_clear_scheduled_hook(self::HOOK_NAME);
    }

    public function wpCronSchedules($schedules)
    {
        if (!isset($schedules['twint1minute'])) {
            $minutes = 1;
            $schedules['twint1minute'] = [
                'interval' => $minutes * 60,
                'display' => __('Once every 1 minute'),
            ];
        }

        return $schedules;
    }

    public function run(): void
    {
        $this->logger->info(
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
            'payment_method' => 'twint_regular',
            'status' => [
                'wc-pending',
            ],
            'date_before' => $time,
        ]);

        foreach ($pendingOrders as $order) {
            $msgNote = __('The order has been cancelled due to expired after ' . $onlyPickOrderFromMinutes . ' minutes.', 'woocommerce-gateway-twint');
            $order->update_status('cancelled', $msgNote);
        }

        $this->logger->info(
            'twintCronJobDone',
            [
                'There are ' . count($pendingOrders) . ' pending orders has run.',
            ]
        );
    }
}
