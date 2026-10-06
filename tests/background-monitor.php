<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Twint\Woo\Container\AliasingContainer;
use Twint\Woo\Container\ContainerFactory;
use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Logger\NullLogger;
use Twint\Woo\Model\Monitor\MonitoringStatus;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\BackgroundMonitor as Queue;
use Twint\Woo\Service\Express\ExpressOrderService;
use Twint\Woo\Service\MonitorService;
use Twint\Woo\Utility\LegacyPkcs12Converter;

class ActionScheduler
{
    public static bool $ready = true;

    public static function is_initialized(): bool
    {
        return self::$ready;
    }
}
class wpdb
{
    public array $queries = [];

    public array $rows = [];

    public int $lockResult = 1;

    public function prepare(string $sql, ...$args): string
    {
        foreach ($args as $arg) {
            $sql = preg_replace('/%[sd]/', "'" . str_replace("'", "''", (string) $arg) . "'", $sql, 1);
        }
        return $sql;
    }

    public function get_results(string $sql): array
    {
        $this->queries[] = $sql;
        return $this->rows;
    }

    public function get_var(string $sql): int
    {
        $this->queries[] = $sql;
        return $this->lockResult;
    }

    public function query(string $sql): int
    {
        $this->queries[] = $sql;
        return 1;
    }
}
$GLOBALS['jobs'] = [];
$GLOBALS['options'] = [];
$GLOBALS['scheduleFails'] = false;
function as_enqueue_async_action($hook, $args = [], $group = '')
{
    return as_schedule_single_action(
        time(),
        $hook,
        $args,
        $group
    );
}
function as_schedule_single_action($when, $hook, $args = [], $group = '')
{
    if ($GLOBALS['scheduleFails']) {
        return 0;
    }
    $id = count($GLOBALS['jobs']) + 1;
    $GLOBALS['jobs'][$id] = compact('when', 'hook', 'args', 'group') + [
        'status' => 'pending',
    ];
    return $id;
}
function as_get_scheduled_actions($query, $format = 'ids')
{
    $ids = [];
    foreach ($GLOBALS['jobs'] as $id => $job) {
        foreach (['hook', 'args', 'group', 'status'] as $key) {
            if ($job[$key] !== $query[$key]) {
                continue 2;
            }
        }
        $ids[] = $id;
    }
    return $ids;
}
function as_unschedule_all_actions($hook, $args = [], $group = '')
{
    foreach ($GLOBALS['jobs'] as &$job) {
        if ($job['hook'] === $hook && $job['group'] === $group && ($args === [] || $job['args'] === $args) && $job['status'] === 'pending') {
            $job['status'] = 'canceled';
        }
    }
}
function get_option($name, $default = false)
{
    return $GLOBALS['options'][$name] ?? $default;
}
function update_option($name, $value, $autoload = null)
{
    $GLOBALS['options'][$name] = $value;
}
function delete_option($name)
{
    unset($GLOBALS['options'][$name]);
}
function add_action(...$args)
{
}
function add_filter(...$args)
{
}
function esc_html($value)
{
    return $value;
}
function wp_next_scheduled($hook)
{
    return false;
}
function wp_schedule_event($time, $schedule, $hook)
{
    $GLOBALS['cron'][] = $hook;
}

$checks = 0;
function check(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    ++$checks;
    echo "PASS: {$message}\n";
}
function throws(callable $fn, string $message): void
{
    try {
        $fn();
    } catch (RuntimeException $e) {
        check(true, $message);
        return;
    }
    check(false, $message);
}
function pairing(string $status = 'IN_PROGRESS', bool $express = false): Pairing
{
    return (new Pairing(false))->load([
        'id' => '11111111-1111-4111-8111-111111111111',
        'status' => $status,
        'is_express' => $express,
        'wc_order_id' => 7,
        'captured' => false,
    ]);
}
class TestRepository extends PairingRepository
{
    public ?Pairing $pairing;

    public array $pending = [];

    public function get(mixed $id): ?Pairing
    {
        return $this->pairing;
    }

    public function loadInProcessPairings(): array
    {
        return $this->pending;
    }
}
class TestMonitor extends MonitorService
{
    public int $calls = 0;

    public bool $fail = false;

    public bool $finish = false;

    public function monitor(Pairing $pairing): MonitoringStatus
    {
        ++$this->calls;
        check(count(as_get_scheduled_actions([
            'hook' => Queue::HOOK,
            'args' => [$pairing->getId()],
            'group' => Queue::GROUP,
            'status' => 'pending',
        ])) > 0, 'retry saved before API call');
        if ($this->fail) {
            throw new RuntimeException('Simulated API timeout');
        }
        return MonitoringStatus::fromValues(
            $this->finish,
            $this->finish ? MonitoringStatus::STATUS_PAID : MonitoringStatus::STATUS_IN_PROGRESS
        );
    }
}
function setServices(array $services): void
{
    $property = new ReflectionProperty(ContainerFactory::class, 'container');
    $property->setValue(null, new AliasingContainer($services));
}
function startJob(): void
{
    foreach ($GLOBALS['jobs'] as &$job) {
        if ($job['status'] === 'pending' && $job['hook'] === Queue::HOOK) {
            $job['status'] = 'in-progress';
            return;
        }
    }
}

check(!function_exists('shell_exec') && !function_exists('proc_open'), 'test process has shell functions disabled');
check(Queue::available(), 'queue availability accepts the actual ActionScheduler class name');
ActionScheduler::$ready = false;
check(!Queue::available(), 'queue unavailable before initialization');
throws(static fn () => Queue::enqueue('id'), 'unavailable queue fails explicitly');
ActionScheduler::$ready = true;
$id = pairing()->getId();
Queue::enqueue($id);
Queue::enqueue($id);
check(count($GLOBALS['jobs']) === 1, 'repeated dispatch does not duplicate a pending job');
$db = new wpdb();
$repository = new TestRepository($db, new NullLogger());
$repository->pairing = pairing();
$monitor = (new ReflectionClass(TestMonitor::class))->newInstanceWithoutConstructor();
setServices([
    'pairing.repository' => $repository,
    'monitor.service' => $monitor,
]);
startJob();
Queue::run($id);
check($monitor->calls === 1 && count($GLOBALS['jobs']) === 2, 'running job schedules its next check');
check($GLOBALS['jobs'][2]['when'] >= time() + 59, 'background retries wait approximately one minute');
startJob();
$monitor->fail = true;
throws(static fn () => Queue::run($id), 'API failure remains visible to the job runner');
check(count($GLOBALS['jobs']) === 3 && $GLOBALS['jobs'][3]['status'] === 'pending', 'API failure preserves the retry');
$monitor->fail = false;
$monitor->finish = true;
startJob();
Queue::run($id);
check(as_get_scheduled_actions([
    'hook' => Queue::HOOK,
    'args' => [$id],
    'group' => Queue::GROUP,
    'status' => 'pending',
]) === [], 'successful payment cancels future checks');
$calls = $monitor->calls;
$repository->pairing = pairing('SUCCESS');
Queue::enqueue($id);
startJob();
Queue::run($id);
check($monitor->calls === $calls, 'already completed payment does not repeat API work');
$repository->pairing = pairing('CANCELLED', true);
Queue::run($id);
check($monitor->calls === $calls, 'cancelled Express payment does not repeat API work');
$repository->pairing = null;
Queue::run($id);
check($monitor->calls === $calls, 'deleted pairing stops cleanly');
$GLOBALS['scheduleFails'] = true;
throws(static fn () => Queue::enqueue('new-id'), 'queue write failure is not reported as success');
$GLOBALS['scheduleFails'] = false;
check(!Queue::health()['valid'], 'unexecuted self-test is not reported as healthy');
Queue::requestHealthCheck();
Queue::requestHealthCheck();
check(count(as_get_scheduled_actions([
    'hook' => Queue::HEALTH_HOOK,
    'args' => [],
    'group' => Queue::GROUP,
    'status' => 'pending',
])) === 1, 'self-test requests are deduplicated');
Queue::recordHealth();
check(Queue::health()['valid'], 'HTTP execution confirms queue health');
update_option(Queue::HEALTH_OPTION, time() - 601);
check(!Queue::health()['valid'], 'old health result expires');
define('WP_CLI', true);
Queue::recordHealth();
check(!Queue::health()['valid'], 'CLI execution cannot verify HTTP queue health');
Queue::initialize();
check(
    in_array('twint_order_handler', $GLOBALS['cron'], true),
    'active plugin update repairs missing recovery schedule'
);
Queue::enqueue('another-pairing');
Queue::deactivate();
check(
    array_filter($GLOBALS['jobs'], static fn ($job) => $job['status'] === 'pending') === [],
    'deactivation clears queued payment and health jobs'
);
check(get_option(Queue::HEALTH_OPTION) === false, 'deactivation clears prior health result');

$table_prefix = 'wp_';
$realRepository = new PairingRepository($db, new NullLogger());
$realRepository->loadInProcessPairings();
$sql = end($db->queries);
check(
    str_contains($sql, "IN ('PAIRING_IN_PROGRESS', 'IN_PROGRESS')"),
    'recovery query matches both statuses separately'
);
check(
    str_contains($sql, 'LIMIT 100') && str_contains($sql, 'COALESCE(checked_at, created_at)'),
    'recovery query is bounded and rotates checked rows'
);
$realRepository->acquireMonitorLock($id);
$lock1 = end($db->queries);
$table_prefix = 'wp_2_';
$realRepository->acquireMonitorLock($id);
$lock2 = end($db->queries);
check($lock1 !== $lock2, 'locks are isolated between multisite stores');
check(str_contains($lock2, ', 0)'), 'lock contention does not block an HTTP request');

// Exercise the actual monitor wrapper, including stale data and exception paths.
$realMonitor = (new ReflectionClass(MonitorService::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(MonitorService::class, 'repository'))->setValue($realMonitor, $repository);
$repository->pairing = pairing('SUCCESS');
$result = $realMonitor->monitor(pairing());
check($result->paid(), 'stale pending input is reloaded after locking');
check(str_contains(end($db->queries), 'RELEASE_LOCK'), 'monitor releases the lock after completion');
$repository->pairing = null;
throws(static fn () => $realMonitor->monitor(pairing()), 'missing pairing produces an explicit error');
check(str_contains(end($db->queries), 'RELEASE_LOCK'), 'monitor releases the lock after an exception');
$db->lockResult = 0;
check(!$realMonitor->monitor(pairing())->finished(), 'concurrent worker reports pending without processing payment');

$repository->pending = [pairing(), pairing('PAIRING_IN_PROGRESS', true)];
$cron = new MonitorPairingCronJob(new NullLogger(), $realMonitor);
$calls = $monitor->calls;
$cron->run();
check($monitor->calls === $calls && count(as_get_scheduled_actions([
    'hook' => Queue::HOOK,
    'args' => [$id],
    'group' => Queue::GROUP,
    'status' => 'pending',
])) === 1, 'recovery queues work without a customer browser or synchronous API calls');
check(!(new LegacyPkcs12Converter())->canRun(), 'optional certificate conversion is unavailable without proc_open');

class FixedMonitor extends MonitorService
{
    public int $calls = 0;

    public MonitoringStatus $result;

    public function monitor(Pairing $pairing): MonitoringStatus
    {
        ++$this->calls;
        return $this->result;
    }
}
$fixed = (new ReflectionClass(FixedMonitor::class))->newInstanceWithoutConstructor();
$express = (new ReflectionClass(ExpressOrderService::class))->newInstanceWithoutConstructor();
$express->setMonitor($fixed);
(new ReflectionProperty($express, 'logger'))->setValue($express, new NullLogger());
$method = new ReflectionMethod($express, 'monitorPairing');
$fixed->result = MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
throws(static fn () => $method->invoke($express, pairing()), 'pending Express confirmation yields for a later request');
check($fixed->calls === 1, 'Express confirmation does not loop inside the HTTP job');
$fixed->result = MonitoringStatus::fromValues(true, MonitoringStatus::STATUS_PAID);
check($method->invoke($express, pairing()) === true, 'confirmed Express payment can complete the order');
$fixed->result = MonitoringStatus::fromValues(true, MonitoringStatus::STATUS_FAILED);
check($method->invoke($express, pairing()) === false, 'definitive Express failure remains a failure');

echo "\n{$checks} checks passed.\n";
