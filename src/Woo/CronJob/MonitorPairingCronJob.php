<?php

declare(strict_types=1);

namespace Twint\Woo\CronJob;

use Throwable;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;

/**
 * @method MonitorService getMonitor()
 */
class MonitorPairingCronJob
{
    use LazyLoadTrait;

    public const HOOK_NAME = 'twint_order_handler';

    protected static array $lazyLoads = ['monitor'];

    public function __construct(
        private readonly WC_Logger_Interface $logger,
        private readonly Lazy|MonitorService $monitor,
    ) {
        add_filter('cron_schedules', [$this, 'addMinuteInterval']);

        add_action(self::HOOK_NAME, [$this, 'run']);
    }

    public static function scheduleCronjob(): void
    {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'every_minute', self::HOOK_NAME);
        }
    }

    public static function removeCronjob(): void
    {
        wp_clear_scheduled_hook(self::HOOK_NAME);
    }

    public function addMinuteInterval($schedules): array
    {
        $schedules['every_minute'] = [
            'interval' => 60, // 60 seconds = 1 minute
            'display' => __('Every Minute'),
        ];

        return $schedules;
    }

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $this->logger->info('twintCronJobRunning', ['Running twint cancel expired orders']);

        $this->getMonitor()->monitors();

        $this->logger->info('twintCronJobDone');
    }
}
