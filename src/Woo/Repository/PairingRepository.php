<?php

declare(strict_types=1);

namespace Twint\Woo\Repository;

use mysqli_result;
use Twint\Woo\Model\Pairing;
use wpdb;

/**
 * TODO fix SQL injection issues
 * use $query =  $this->>db->prepare
 */
class PairingRepository
{
    public function __construct(
        private readonly wpdb $db
    ) {
    }

    public function updateCheckedAt(Pairing $pairing): mysqli_result|bool|int|null
    {
        $table = $pairing->getTableName();
        $pairingId = $pairing->getId();

        return $this->db->query("UPDATE {$table} SET checked_at = NOW() WHERE id = '{$pairingId}';");
    }

    public function loadInProcessPairings(): array
    {
        $table = Pairing::getTableName();
        $select = $this->getSelect();
        $results = $this->db->get_results(
            "SELECT {$select} FROM {$table} WHERE status IN ('PAIRING_IN_PROGRESS', 'IN_PROGRESS') ORDER BY created_at ASC"
        );
        $pairings = [];
        foreach ($results as $result) {
            $instance = new Pairing();
            $result = (array) $result;
            $pairings[] = $instance->load($result);
        }

        return $pairings;
    }

    public function findByWooOrderId($orderId): ?Pairing
    {
        $table = Pairing::getTableName();
        $select = $this->getSelect();
        $result = $this->db->get_results("SELECT {$select} FROM {$table} WHERE wc_order_id = {$orderId} LIMIT 1");
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array) reset($result));
    }

    public function findById(mixed $pairingId): ?Pairing
    {
        $table = Pairing::getTableName();
        $select = $this->getSelect();
        $result = $this->db->get_results(
            "SELECT {$select}
                    FROM {$table} 
                    WHERE id = '{$pairingId}' 
                    LIMIT 1"
        );
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array) reset($result)) ?? null;
    }

    private function getSelect(): string
    {
        return '*, 
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(checked_at)) AS checked_ago,
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(created_at)) AS created_ago 
               ';
    }
}
