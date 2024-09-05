<?php

namespace Twint\Woo\Migrations;

final class CreateTwintPairingTable
{
    public static string $tableName = 'twint_pairing';

    public static function up(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tableName = self::$tableName;

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$tableName} (
            id VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            shipping_method_id INT UNSIGNED DEFAULT NULL,
            wc_order_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED DEFAULT NULL,
            customer_data longtext DEFAULT NULL,
            is_express bool NOT NULL DEFAULT false,
            amount DECIMAL(19,2) unsigned NOT NULL,
            status VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, 
            transaction_status VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            pairing_status VARCHAR(255) NULL,
            is_ordering INT UNSIGNED NOT NULL DEFAULT 0,
            checked_at DATETIME(3) NULL DEFAULT NULL,
            version INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME(3) NOT NULL,
            updated_at DATETIME(3) NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $wpdb->query($sql);
    }

    public static function down(): void
    {
        global $wpdb;
        $tableName = self::$tableName;
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
}