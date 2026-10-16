<?php

declare(strict_types=1);

namespace Twint\Woo\Setup\Migration;

use Twint\Woo\Repository\PairingRepository;
use wpdb;

final class CreatePairingTable
{
    public function __construct(
        private readonly wpdb $db
    ) {
    }

    public function up(): void
    {
        $this->createTable();
        $this->addNewColumns();

        // Only drop trigger when replacement columns exist, OR when trigger
        // itself is broken (DEFINER deleted → 1449 on every UPDATE).
        // Dropping without columns = zero concurrency guard = regression.
        if ($this->columnsExist(['lock_version', 'order_settled'])
            || get_option('twint_trigger_definer_error', false)
        ) {
            $this->tryDropTrigger();
        }
    }

    private function addNewColumns(): void
    {
        $table = PairingRepository::tableName();

        // Check which columns already exist (idempotent)
        $existing = $this->db->get_col($this->db->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
               AND COLUMN_NAME IN ('lock_version', 'order_settled')",
            $table
        ));

        $toAdd = [];
        if (!in_array('lock_version', $existing, true)) {
            $toAdd[] = 'ADD COLUMN `lock_version` INT UNSIGNED NOT NULL DEFAULT 0';
        }
        if (!in_array('order_settled', $existing, true)) {
            $toAdd[] = 'ADD COLUMN `order_settled` TINYINT UNSIGNED NOT NULL DEFAULT 0';
        }

        if ($toAdd === []) {
            return; // both columns already exist
        }

        $this->db->query(
            "ALTER TABLE `{$table}` " . implode(', ', $toAdd)
        );

        if (!empty($this->db->last_error)) {
            error_log('TWINT: could not add columns: ' . $this->db->last_error);
        }
    }

    private function tryDropTrigger(): void
    {
        $this->db->query('DROP TRIGGER IF EXISTS `before_update_twint_pairing`');
        if (!empty($this->db->last_error)) {
            error_log('TWINT: could not drop trigger (harmless): ' . $this->db->last_error);
        }
    }

    private function columnsExist(array $names): bool
    {
        $table = PairingRepository::tableName();
        $cols = $this->db->get_col($this->db->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
               AND COLUMN_NAME IN ('" . implode("','", $names) . "')",
            $table
        ));

        return count($cols) === count($names);
    }

    private function createTable(): void
    {
        $tableName = PairingRepository::tableName();

        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
          `id` varchar(36) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `wc_order_id` int unsigned NOT NULL,
          `token` varchar(32) NULL,
          `amount` decimal(19,2) unsigned NOT NULL,
          `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
          `transaction_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `pairing_status` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
          `is_express` tinyint(1) NOT NULL DEFAULT '0',   
          `shipping_method_id` varchar(128) DEFAULT NULL,
          `customer_data` longtext COLLATE utf8mb4_unicode_520_ci,
          `is_ordering` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `captured` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `version` int unsigned NOT NULL DEFAULT '1',
          `checked_at` datetime DEFAULT NULL,
          `created_at` datetime DEFAULT NOW(),
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $tableName = PairingRepository::tableName();
        $this->db->query("DROP TABLE IF EXISTS {$tableName}");
    }
}
