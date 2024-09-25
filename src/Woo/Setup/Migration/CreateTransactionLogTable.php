<?php

declare(strict_types=1);

namespace Twint\Woo\Setup\Migration;

use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use wpdb;

final class CreateTransactionLogTable
{
    public function __construct(
        private readonly wpdb $db
    ) {
    }

    public function up(): void
    {
        $table = TransactionRepository::tableName();
        $pairingTable = PairingRepository::tableName();

        $query =
            "CREATE TABLE IF NOT EXISTS `{$table}` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `pairing_id` varchar(36) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
              `order_id` int unsigned NULL,
              `soap_action` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
              `api_method` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
              `request` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `response` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `soap_request` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `soap_response` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `exception_text` longtext COLLATE utf8mb4_unicode_520_ci DEFAULT '',
              `created_at` datetime DEFAULT NOW(),
              PRIMARY KEY (`id`),
              FOREIGN KEY (pairing_id) REFERENCES {$pairingTable}(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

        $this->db->query($query);

        $this->createIndex();
    }

    private function createIndex(): void
    {
        // Define the table name and index name
        $tableName = TransactionRepository::tableName();
        $indexName = 'twint_transaction_log_order_idx';

        // Check if the index already exists
        $exists = $this->db->get_var(
            $this->db->prepare(
                'SELECT COUNT(1) 
                 FROM information_schema.statistics 
                 WHERE table_name = %s 
                 AND index_name = %s',
                [$tableName, $indexName]
            )
        );

        // If the index does not exist, create it
        if (!$exists) {
            $query = "CREATE INDEX {$indexName} ON {$tableName}(order_id);";

            // Run the query to create the index
            $this->db->query($query);
        }
    }

    public function down(): void
    {
        $tableName = TransactionRepository::tableName();

        $this->db->query("DROP TABLE IF EXISTS {$tableName}");
    }
}
