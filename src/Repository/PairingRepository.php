<?php

declare(strict_types=1);

namespace Twint\Woo\Repository;

use Exception;
use mysqli_result;
use Throwable;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Exception\DatabaseException;
use Twint\Woo\Model\Pairing;
use WC_Logger_Interface;
use wpdb;

class PairingRepository
{
    private ?bool $schemaReady = null;

    public function __construct(
        private readonly wpdb                $db,
        private readonly WC_Logger_Interface $logger,
    ) {
    }

    public static function tableName(): string
    {
        global $table_prefix;

        return $table_prefix . 'twint_pairing';
    }

    /**
     * Build a lock name scoped to this database + table prefix.
     * GET_LOCK names are server-global; without a scope prefix, shops on
     * the same MySQL server would collide.
     */
    public static function scopedLockName(string $suffix): string
    {
        // md5 prefix (8 chars) + '_' + suffix → max 64 chars.
        $scope = substr(md5(DB_NAME . self::tableName()), 0, 8);
        return substr($scope . '_' . $suffix, 0, 64);
    }

    /**
     * @throws Throwable
     */
    public function save(Pairing $pairing): Pairing
    {
        if ($pairing->isNewRecord()) {
            return $this->insert($pairing);
        }

        return $this->update($pairing);
    }

    public function insert(Pairing $pairing): Pairing
    {
        try {
            $customer = $pairing->getCustomerData();

            $this->db->insert(self::tableName(), [
                'id' => $pairing->getId(),
                'token' => $pairing->getToken(),
                'shipping_method_id' => $pairing->getShippingMethod(),
                'wc_order_id' => $pairing->getWcOrderId(),
                'ref_id' => $pairing->getRefId(),
                'customer_data' => $customer === [] ? null : wp_json_encode($customer),
                'is_express' => $pairing->getIsExpress(),
                'amount' => $pairing->getAmount(),
                'status' => $pairing->getStatus(),
                'transaction_status' => $pairing->getTransactionStatus(),
                'pairing_status' => $pairing->getPairingStatus(),
                'is_ordering' => $pairing->getIsOrdering(),
                'checked_at' => $pairing->getCheckedAt(),
                'captured' => $pairing->isCaptured(),
            ]);

            return $this->get($pairing->getId());
        } catch (Exception $e) {
            $this->logger->error('TWINT PairingRepository::insert: ' . $e->getMessage(), [
                'source' => 'twint-woocommerce-extension',
                'pairing_id' => $pairing->getId(),
                'wc_order_id' => $pairing->getWcOrderId(),
            ]);

            throw $e;
        }
    }

    public function get(mixed $id): ?Pairing
    {
        $select = $this->getSelect();

        $table = self::tableName();
        $query = $this->db->prepare("SELECT {$select} FROM {$table} WHERE id = %s LIMIT 1;", $id);

        return ($result = $this->db->get_results($query)) ? (new Pairing(false))->load((array) reset($result)) : null;
    }

    private function getSelect(): string
    {
        return '*,
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(checked_at)) AS checked_ago,
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(created_at)) AS created_ago
               ';
    }

    /**
     * True when both lock_version and order_settled columns exist.
     * Used to guard: update() locking, updateStatus() lock_version increment,
     * reconciliation sweep queries, and markOrderSettled().
     * In legacy mode (ALTER failed) this returns false and all those code
     * paths fall back to 1.7.8 behavior — no "Unknown column" errors.
     */
    public function isSchemaReady(): bool
    {
        if ($this->schemaReady === null) {
            $this->schemaReady = (int) get_option('twint_db_version', 0) >= TwintConstant::TWINT_DB_VERSION;
        }

        return $this->schemaReady;
    }

    private function isTriggerDefinerError(): bool
    {
        /** @phpstan-ignore-next-line property.protected */
        return $this->db->dbh instanceof \mysqli && mysqli_errno($this->db->dbh) === 1449;
    }

    /**
     * @throws Exception|Throwable
     */
    public function update(Pairing $pairing): Pairing
    {
        try {
            $customerData = $pairing->getCustomerData();

            $data = [
                'token' => $pairing->getToken(),
                'shipping_method_id' => $pairing->getShippingMethod(),
                'wc_order_id' => $pairing->getWcOrderId(),
                'ref_id' => $pairing->getRefId(),
                'customer_data' => $customerData === [] ? null : wp_json_encode($customerData),
                'is_express' => $pairing->getIsExpress(),
                'amount' => $pairing->getAmount(),
                'status' => $pairing->getStatus(),
                'transaction_status' => $pairing->getTransactionStatus(),
                'pairing_status' => $pairing->getPairingStatus(),
                'is_ordering' => $pairing->getIsOrdering(),
                'checked_at' => $pairing->getCheckedAt(),
                'created_at' => $pairing->getCreatedAt(),
                'captured' => $pairing->isCaptured(),
            ];

            $where = [
                'id' => $pairing->getId(),
            ];

            if ($this->isSchemaReady()) {
                $data['lock_version'] = $pairing->getLockVersion() + 1;
                $where['lock_version'] = $pairing->getLockVersion();
            } else {
                // Legacy mode: pass version for trigger compatibility
                $data['version'] = $pairing->getVersion();
            }

            $result = $this->db->update(self::tableName(), $data, $where);

            // Check order: (1) SQL error, (2) 1449, (3) conflict
            if ($result === false) {
                if ($this->isTriggerDefinerError()) {
                    update_option('twint_trigger_definer_error', true);
                    // Force next request to re-run migration → drop broken trigger.
                    // Without this, ALL payments fail for the entire retry throttle period.
                    delete_transient('twint_migration_retry_after');
                    $this->logger->error(
                        'TWINT: trigger DEFINER error — ask hosting to run: '
                        . 'DROP TRIGGER IF EXISTS before_update_twint_pairing',
                        [
                            'source' => 'twint-woocommerce-extension',
                        ]
                    );
                }

                throw new DatabaseException($this->db->last_error);
            }

            if ((int) $result === 0) {
                throw new DatabaseException(TwintConstant::EXCEPTION_VERSION_CONFLICT);
            }

            if (get_option('twint_trigger_definer_error', false)) {
                delete_option('twint_trigger_definer_error');
            }

            return $this->get($pairing->getId());
        } catch (Throwable $e) {
            if (!($e instanceof DatabaseException)) {
                $this->logger->error('TWINT PairingRepository::update: ' . $e->getMessage(), [
                    'source' => 'twint-woocommerce-extension',
                    'pairing_id' => $pairing->getId(),
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]);
            }

            throw $e;
        }
    }

    public function updateCheckedAt(Pairing $pairing): mysqli_result|bool|int|null
    {
        $table = self::tableName();
        $query = $this->db->prepare("UPDATE {$table} SET checked_at = NOW() WHERE id = %s;", $pairing->getId());

        return $this->db->query($query);
    }

    public function loadInProcessPairings(): array
    {
        $select = $this->getSelect();
        $table = self::tableName();
        $upgradedAt = get_option('twint_upgraded_at', '1970-01-01');

        $query = $this->db->prepare(
            "SELECT {$select} FROM {$table}
             WHERE status IN (%s, %s)
               AND created_at > %s
               AND created_at > DATE_SUB(NOW(), INTERVAL 48 HOUR)
               AND (checked_at IS NULL OR checked_at < DATE_SUB(NOW(), INTERVAL 20 SECOND))
             ORDER BY created_at DESC
             LIMIT 50",
            'PAIRING_IN_PROGRESS',
            'IN_PROGRESS',
            $upgradedAt
        );

        $results = $this->db->get_results($query);
        $pairings = [];
        foreach ($results as $result) {
            $pairings[] = (new Pairing(false))->load((array) $result);
        }

        return $pairings;
    }

    public function findByWooOrderId(int $orderId): array
    {
        $select = $this->getSelect();
        $table = self::tableName();
        $query = $this->db->prepare(
            "SELECT {$select} FROM {$table} WHERE wc_order_id = %d ORDER BY created_at DESC;",
            $orderId
        );

        $results = $this->db->get_results($query);
        $pairings = [];
        foreach ($results as $result) {
            $pairings[] = (new Pairing(false))->load((array) $result);
        }

        return $pairings;
    }

    public function markAsOrdering(string $id): mysqli_result|bool|int|null
    {
        $table = self::tableName();
        $query = $this->db->prepare("UPDATE {$table} SET is_ordering = 1 WHERE id = %s;", $id);

        return $this->db->query($query);
    }

    public function markAsCancelled(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_CANCELLED);
    }

    private function updateStatus(string $id, string $status): bool|int
    {
        $table = self::tableName();
        $lockClause = $this->isSchemaReady() ? ', lock_version = lock_version + 1' : '';

        $query = $this->db->prepare(
            "UPDATE {$table} SET status = %s {$lockClause}
             WHERE id = %s
               AND status NOT IN ('SUCCESS', 'FAILURE', 'PAID', 'CANCELLED', 'MERCHANT_CANCELLED', 'FAILED')",
            $status,
            $id
        );

        $result = $this->db->query($query);

        // Log when the terminal guard blocked the transition.
        // Callers (markAsPaid, markAsFailed, etc.) do not check the return value,
        // so this log is the only signal that the in-memory Pairing object may
        // diverge from DB state.
        if ($result === 0) {
            $this->logger->info(
                "TWINT updateStatus: transition to '{$status}' blocked for pairing {$id} "
                . '(already in terminal status)',
                [
                    'source' => 'twint-woocommerce-extension',
                ]
            );
        }

        return $result;
    }

    public function markAsFailed(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_FAILED);
    }

    public function markAsPaid(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_PAID);
    }

    public function markAsMerchantCancelled(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED);
    }

    /**
     * Acquire a short, per-pairing advisory lock so that only one worker
     * (HTTP poll vs cron/CLI) runs confirmOrder for a pairing at a time.
     * Non-blocking (timeout 0): returns false immediately if held elsewhere.
     */
    public function acquireConfirmLock(string $id): bool
    {
        return (int) $this->db->get_var(
            $this->db->prepare('SELECT GET_LOCK(%s, 0)', $this->confirmLockName($id))
        ) === 1;
    }

    public function releaseConfirmLock(string $id): void
    {
        $this->db->get_var($this->db->prepare('SELECT RELEASE_LOCK(%s)', $this->confirmLockName($id)));
    }

    private function confirmLockName(string $id): string
    {
        // MySQL lock names are capped at 64 chars; 'twint_cfm_' (10) + uuid (36) = 46.
        return 'twint_cfm_' . $id;
    }

    public function acquireLock(string $name): bool
    {
        return (int) $this->db->get_var($this->db->prepare('SELECT GET_LOCK(%s, 0)', $name)) === 1;
    }

    public function releaseLock(string $name): void
    {
        $this->db->get_var($this->db->prepare('SELECT RELEASE_LOCK(%s)', $name));
    }

    /**
     * Load regular checkout pairings that reached SUCCESS but whose order
     * may not have been settled. Filters on order_settled = 0 to skip
     * already-settled pairings (prevents LIMIT starvation on high-volume shops).
     *
     * @return Pairing[]
     */
    public function loadUnsettledRegularPairings(): array
    {
        $select = $this->getSelect();
        $table = self::tableName();
        $upgradedAt = get_option('twint_upgraded_at', '1970-01-01');

        $query = $this->db->prepare(
            "SELECT {$select} FROM {$table}
             WHERE status = 'SUCCESS' AND is_express = 0 AND captured = 0
               AND order_settled = 0
               AND created_at > %s
               AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY created_at DESC
             LIMIT 50",
            $upgradedAt
        );

        $results = $this->db->get_results($query);
        $pairings = [];
        foreach ($results as $result) {
            $pairings[] = (new Pairing(false))->load((array) $result);
        }

        return $pairings;
    }

    /**
     * Load Express pairings that reached PAID (via timeout backstop or
     * normal flow) but whose WC order may not have been settled.
     *
     * @return Pairing[]
     */
    public function loadUnsettledExpressPairings(): array
    {
        $select = $this->getSelect();
        $table = self::tableName();
        $upgradedAt = get_option('twint_upgraded_at', '1970-01-01');

        $query = $this->db->prepare(
            "SELECT {$select} FROM {$table}
             WHERE status = 'PAID' AND is_express = 1
               AND order_settled = 0
               AND created_at > %s
               AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY created_at DESC
             LIMIT 50",
            $upgradedAt
        );

        $results = $this->db->get_results($query);
        $pairings = [];
        foreach ($results as $result) {
            $pairings[] = (new Pairing(false))->load((array) $result);
        }

        return $pairings;
    }

    /**
     * Mark a pairing as settled so the reconciliation sweep skips it.
     *
     * @param string $id Pairing ID
     * @param int $value ORDER_SETTLED_DONE (1), _NEEDS_REVIEW (2), _SKIPPED (3), or _REOPENED (4)
     */
    public function markOrderSettled(string $id, int $value = TwintConstant::ORDER_SETTLED_DONE): void
    {
        if (!$this->isSchemaReady()) {
            return;
        }

        $table = self::tableName();

        // When writing DONE (1), only overwrite PENDING (0) — never downgrade
        // a higher-value flag like REOPENED (4) back to DONE.
        $guard = $value === TwintConstant::ORDER_SETTLED_DONE ? ' AND order_settled = 0' : '';

        $this->db->query(
            $this->db->prepare("UPDATE {$table} SET order_settled = %d WHERE id = %s{$guard}", $value, $id)
        );
    }
}
