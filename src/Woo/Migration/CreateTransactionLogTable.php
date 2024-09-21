<?php

declare(strict_types=1);

namespace Twint\Woo\Migration;

use Twint\Woo\Repository\TransactionRepository;

final class CreateTransactionLogTable
{
    public static function up(): void
    {
        global $wpdb;

        $table = TransactionRepository::tableName();

        $queries = [
            "CREATE TABLE `$table` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `pairing_id` varchar(36) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
              `order_id` int unsigned NULL,
              `soap_action` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
              `api_method` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
              `request` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `response` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `soap_request` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `soap_response` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `exception_text` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `created_at` datetime DEFAULT NOW(),
              PRIMARY KEY (`id`),
              FOREIGN KEY (pairing_id) REFERENCES wp_twint_pairing(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

            "CREATE INDEX twint_transaction_log_order_idx ON $table(order_id);"
        ];

        foreach ($queries as $query){
            $wpdb->query($query);
        }
    }

    public static function down(): void
    {
        global $wpdb;
        $tableName = TransactionRepository::tableName();

        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
}
