<?php

declare(strict_types=1);

namespace Twint\Woo\Repository;

use Twint\Woo\Model\Pairing;

class PairingRepository
{
    public function updateCheckedAt(Pairing $pairing): mysqli_result|bool|int|null
    {
        global $wpdb;
        $table = $pairing->getTableName();
        $pairingId = $pairing->getId();

        return $wpdb->query("UPDATE {$table} SET checked_at = NOW() WHERE id = '{$pairingId}';");
    }

    public function loadInProcessPairings(): array
    {
        global $wpdb;
        $table = Pairing::getTableName();
        $results = $wpdb->get_results(
            "SELECT *, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(checked_at)) AS checked_ago FROM {$table} WHERE status IN ('PAIRING_IN_PROGRESS', 'IN_PROGRESS') ORDER BY created_at ASC"
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
        global $wpdb;
        $table = Pairing::getTableName();
        $result = $wpdb->get_results(
            "SELECT *, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(checked_at)) AS checked_ago FROM {$table} WHERE wc_order_id = {$orderId} LIMIT 1"
        );
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array) reset($result));
    }

    public function findById(mixed $pairingId): ?Pairing
    {
        global $wpdb;
        $table = Pairing::getTableName();
        $result = $wpdb->get_results(
            "SELECT *, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(checked_at)) AS checked_ago FROM {$table} WHERE id = '{$pairingId}' LIMIT 1"
        );
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array) reset($result)) ?? null;
    }
}
