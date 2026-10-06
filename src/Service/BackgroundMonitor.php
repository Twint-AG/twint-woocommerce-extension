<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use ActionScheduler;
use RuntimeException;
use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Plugin;

/** Runs one payment check per job. Checkout HTTP polls remain immediate. */
final class BackgroundMonitor
{
    public const HOOK = 'twint_background_check';

    public const HEALTH_HOOK = 'twint_background_health';

    public const GROUP = 'twint-payments';

    public const HEALTH_OPTION = 'twint_background_http_checked_at';

    public const RETRY_SECONDS = 60;

    public static function register(): void
    {
        add_action(self::HOOK, [self::class, 'run']);
        add_action(self::HEALTH_HOOK, [self::class, 'recordHealth']);
        add_action('init', [self::class, 'initialize'], 20);
    }

    public static function available(): bool
    {
        return function_exists('as_enqueue_async_action')
            && function_exists('as_schedule_single_action')
            && function_exists('as_get_scheduled_actions')
            && function_exists('as_unschedule_all_actions')
            && class_exists('ActionScheduler')
            && ActionScheduler::is_initialized();
    }

    public static function initialize(): void
    {
        // Updating an active plugin does not invoke its activation hook.
        MonitorPairingCronJob::scheduleCronjob();
        self::requestHealthCheck();
    }

    public static function enqueue(string $pairingId, int $delay = 0): void
    {
        if (!self::available()) {
            throw new RuntimeException('TWINT requires an initialized Action Scheduler queue.');
        }
        $args = [$pairingId];
        // Exclude the running job, so it can save its successor before API I/O.
        if (self::pending(self::HOOK, $args)) {
            return;
        }
        $id = $delay > 0
            ? as_schedule_single_action(time() + $delay, self::HOOK, $args, self::GROUP)
            : as_enqueue_async_action(self::HOOK, $args, self::GROUP);
        if (!$id) {
            throw new RuntimeException('TWINT could not save a background payment check.');
        }
    }

    public static function run(string $pairingId): void
    {
        $repository = Plugin::di('pairing.repository', false);
        $pairing = $repository->get($pairingId);
        if (!$pairing instanceof Pairing || $pairing->isFinished()) {
            self::cancel($pairingId);
            return;
        }
        // A timeout or terminated request must not lose the next check.
        self::enqueue($pairingId, self::RETRY_SECONDS);
        $status = Plugin::di('monitor.service', false)->monitor($pairing);
        if ($status->finished()) {
            self::cancel($pairingId);
        }
        // Let exceptions reach Action Scheduler so failed jobs remain visible.
    }

    public static function cancel(string $pairingId): void
    {
        if (self::available()) {
            as_unschedule_all_actions(self::HOOK, [$pairingId], self::GROUP);
        }
    }

    public static function requestHealthCheck(): void
    {
        if (!self::available() || self::healthAge() < 300 || self::pending(self::HEALTH_HOOK, [])) {
            return;
        }
        as_enqueue_async_action(self::HEALTH_HOOK, [], self::GROUP);
    }

    public static function recordHealth(): void
    {
        // A CLI test cannot prove that the HTTP queue runner works.
        if (!defined('WP_CLI') || !WP_CLI) {
            update_option(self::HEALTH_OPTION, time(), false);
        }
    }

    public static function health(): array
    {
        if (!self::available()) {
            return [
                'valid' => false,
                'message' => __('Action Scheduler is unavailable. TWINT cannot run background checks.', 'twint-woocommerce-extension'),
            ];
        }
        if (self::healthAge() <= 600) {
            return [
                'valid' => true,
                'message' => __('An HTTP background test completed successfully within the last 10 minutes.', 'twint-woocommerce-extension'),
            ];
        }
        return [
            'valid' => false,
            'message' => __('HTTP background execution is not yet verified. Refresh this page after one minute. If this message remains, check WP-Cron and HTTP loopback requests.', 'twint-woocommerce-extension'),
        ];
    }

    public static function deactivate(): void
    {
        if (self::available()) {
            as_unschedule_all_actions(self::HOOK, [], self::GROUP);
            as_unschedule_all_actions(self::HEALTH_HOOK, [], self::GROUP);
        }
        delete_option(self::HEALTH_OPTION);
    }

    private static function healthAge(): int
    {
        $checkedAt = (int) get_option(self::HEALTH_OPTION, 0);
        return $checkedAt > 0 && $checkedAt <= time() ? time() - $checkedAt : PHP_INT_MAX;
    }

    private static function pending(string $hook, array $args): bool
    {
        return as_get_scheduled_actions([
            'hook' => $hook,
            'args' => $args,
            'group' => self::GROUP,
            'status' => 'pending',
            'per_page' => 1,
        ], 'ids') !== [];
    }
}
