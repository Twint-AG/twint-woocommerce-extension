<?php

namespace Twint\Woo\Migrations;

final class CreateTwintTransactionLogTable
{
    public static string $tableName = 'twint_transactions_log';

    public static function up(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tableName = self::$tableName;

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$tableName} (
            record_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            pairing_id VARCHAR(36) NULL,
			order_id INT UNSIGNED NOT NULL,
			transaction_id INT UNSIGNED NOT NULL,
			order_status varchar(191) NOT NULL default '',
			soap_action varchar(191) NOT NULL default '',
			api_method varchar(191) NOT NULL default '',
			request longtext NOT NULL,
			response longtext NOT NULL,
			soap_request longtext NOT NULL,
			soap_response longtext NOT NULL,
			exception_text longtext NOT NULL,
			created_at DATETIME,
			updated_at DATETIME,
			PRIMARY KEY (record_id)
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