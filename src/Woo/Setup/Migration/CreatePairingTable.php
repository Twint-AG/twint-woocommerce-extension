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
        $this->createTrigger();
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

    private function createTrigger(): void
    {
        $tableName = PairingRepository::tableName();
        $sql = "CREATE TRIGGER `before_update_twint_pairing` BEFORE UPDATE ON `{$tableName}` FOR EACH ROW BEGIN
	
                DECLARE changed_columns INT;
            
                -- Perform version check
                IF OLD.version <> NEW.version THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version conflict detected. Update aborted.';
                END IF;
                
                SET changed_columns = 0;
                            
                IF NEW.status <> OLD.status THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.token <> OLD.token OR (NEW.token IS NULL XOR OLD.token IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                                
                IF NEW.shipping_method_id <> OLD.shipping_method_id OR (NEW.shipping_method_id IS NULL XOR OLD.shipping_method_id IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.wc_order_id <> OLD.wc_order_id OR (NEW.wc_order_id IS NULL XOR OLD.wc_order_id IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.customer_data <> OLD.customer_data OR (NEW.customer_data IS NULL XOR OLD.customer_data IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
               
                IF NEW.is_express <> OLD.is_express THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.amount <> OLD.amount THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.pairing_status <> OLD.pairing_status OR (NEW.pairing_status IS NULL XOR OLD.pairing_status IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;
                
                IF NEW.transaction_status <> OLD.transaction_status OR (NEW.transaction_status IS NULL XOR OLD.transaction_status IS NULL) THEN
                    SET changed_columns = changed_columns + 1;
                END IF;          
               
               IF changed_columns > 0 THEN
                  SET NEW.version = OLD.version + 1;
               END IF;  
                    
            END;";

        $showTrigger = "SHOW TRIGGERS where `TRIGGER` = 'before_update_twint_pairing'";
        if ($this->db->query($showTrigger) === 0) {
            $this->db->query($sql);
        }
    }

    public function down(): void
    {
        $tableName = PairingRepository::tableName();
        $this->db->query("DROP TABLE IF EXISTS {$tableName}");
    }
}
