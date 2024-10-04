<?php

declare(strict_types=1);

namespace Twint\Woo\Setup\Migration;

use Twint\Woo\Repository\PairingRepository;
use wpdb;

final class AddReferenceIdColumnToPairingTable
{
    public function __construct(
        private readonly wpdb $db
    ) {
    }

    public function up(): void
    {
        $this->createTable();
    }

    private function createTable(): void
    {
        $tableName = PairingRepository::tableName();
        $column  = 'ref_id';

        // Query to check if the column exists in the specified table
        $exist = $this->db->get_var( $this->db->prepare( "
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = %s
                AND COLUMN_NAME = %s
                AND TABLE_SCHEMA = %s
            ", $tableName, $column, DB_NAME )
        );

        // Check the result and act accordingly
        if ( !$exist ) {
            $this->db->query("ALTER TABLE $tableName ADD $column VARCHAR(36) DEFAULT NULL");
        }
    }

    public function down(): void
    {
        $tableName = PairingRepository::tableName();
        $column  = 'ref_id';

        $this->db->query("ALTER TABLE $tableName DROP COLUMN $column");
    }
}
