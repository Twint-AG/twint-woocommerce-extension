<?php

declare(strict_types=1);

namespace Twint\Woo\Repository;

class TransactionRepository
{
    public static function getTableName(): string
    {
        global $table_prefix;
        return $table_prefix . 'twint_transactions_log';
    }

    /**
     * Get transactions log for order
     */
    public function getLogTransactions(int $orderId): array
    {
        global $wpdb;
        $result = $wpdb->get_results('SELECT * from `' . self::getTableName() . "` WHERE order_id = {$orderId};");
        $ret = [];
        foreach ($result as $row) {
            $ret[] = (array) $row;
        }

        return $ret;
    }

    public function getLogTransactionDetails(int $recordId): ?array
    {
        global $wpdb;
        $result = $wpdb->get_results(
            'SELECT * from `' . self::getTableName() . "` WHERE record_id = {$recordId} LIMIT 1;"
        );

        if (empty($result)) {
            return null;
        }

        return (array) reset($result);
    }
}
