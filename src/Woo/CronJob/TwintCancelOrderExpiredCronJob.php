<?php

namespace TWINT\Woo\CronJob;

class TwintCancelOrderExpiredCronJob
{
    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'wpCronSchedules']);

        add_action('twint_cancel_expired_orders', [$this, 'twintCancelExpiredOrders']);
    }

    public static function INIT_CRONJOB(): void
    {
        if (!wp_next_scheduled('twint_cancel_expired_orders')) {
            wp_schedule_event(time(), 'twint15minutes', 'twint_cancel_expired_orders');
        }
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
        $onlyPickOrderFromMinutes = get_option('only_pick_order_from_minutes', 30);
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