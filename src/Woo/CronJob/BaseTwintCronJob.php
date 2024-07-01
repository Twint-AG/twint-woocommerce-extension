<?php

namespace TWINT\Woo\CronJob;

class BaseTwintCronJob
{
    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'wpCronSchedules']);
    }

    public function wpCronSchedules($schedules)
    {
        if (!isset($schedules['twint15minutes'])) {
            $schedules['twint10minutes'] = [
                'interval' => 15 * 60,
                'display' => __('Once every 15 minutes'),
            ];
        }

        return $schedules;
    }
}