<?php

declare(strict_types=1);

namespace Twint\Woo\Migration;

use Twint\Woo\Repository\PairingRepository;

final class CreatePairingTable
{
    public static function up(): void
    {
        global $wpdb;
        $tableName = PairingRepository::tableName();

        $sql = "CREATE TABLE `$tableName` (
          `id` varchar(36) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `wc_order_id` int unsigned NOT NULL,
          `token` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `amount` decimal(19,2) unsigned NOT NULL,
          `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
          `transaction_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `pairing_status` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
          `is_express` tinyint(1) NOT NULL DEFAULT '0',   
          `shipping_method_id` varchar(128) DEFAULT NULL,
          `customer_data` longtext COLLATE utf8mb4_unicode_520_ci,
          `is_ordering` int unsigned NOT NULL DEFAULT '0',
          `version` int unsigned NOT NULL DEFAULT '1',
          `checked_at` datetime(3) DEFAULT NULL,
          `created_at` datetime(3) DEFAULT NOW(),
          `updated_at` datetime(3) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

        $wpdb->query($sql);
    }

    public static function down(): void
    {
        global $wpdb;
        $tableName = PairingRepository::tableName();
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
}
