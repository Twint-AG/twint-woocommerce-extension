<?php

declare(strict_types=1);

namespace Twint\Woo\Repository;

use Exception;
use Twint\Woo\Model\TransactionLog;
use WC_Logger_Interface;
use wpdb;

class TransactionRepository
{
    public function __construct(
        private readonly wpdb                $db,
        private readonly WC_Logger_Interface $logger,
    ) {
    }

    public static function tableName(): string
    {
        global $table_prefix;
        return $table_prefix . 'twint_transactions_log';
    }

    /**
     * @throws Exception
     */
    public function updatePartial(TransactionLog $log, array $values, bool $reload = false): TransactionLog
    {
        try {
            $this->db->update(self::tableName(), $values, [
                'id' => $log->getId(),
            ]);

            return $reload ? $this->get($log->getId()) : $log;
        } catch (Exception $e) {
            $this->logger->error('TWINT TransactionRepository::updatePartial: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function update(TransactionLog $log, bool $reload = false): TransactionLog
    {
        try {
            $this->db->update(self::tableName(), [
                'pairing_id' => $log->getPairingId(),
                'order_id' => $log->getOrderId(),
                'soap_action' => $log->getSoapAction(),
                'api_method' => $log->getApiMethod(),
                'request' => $log->getRequest(),
                'response' => $log->getResponse(),
                'soap_request' => $log->getSoapRequest(),
                'soap_response' => $log->getSoapResponse(),
                'exception_text' => $log->getExceptionText(),
            ], [
                'id' => $log->getId(),
            ]);

            return $reload ? $this->get($log->getId()) : $log;
        } catch (Exception $e) {
            $this->logger->error('TWINT TransactionRepository::update: ' . $e->getMessage());
            throw $e;
        }
    }

    public function get(int $id): ?TransactionLog
    {
        $table = self::tableName();
        $query = $this->db->prepare("SELECT * from {$table} WHERE id = %d ;", $id);

        $result = $this->db->get_results($query);

        return empty($result) ? null : (new TransactionLog(false))->load(reset($result));
    }

    /**
     * @throws Exception
     */
    public function save(TransactionLog $log): TransactionLog
    {
        return $log->isNewRecord() ? $this->insert($log, true) : $this->update($log);
    }

    /**
     * @throws Exception
     */
    public function insert(TransactionLog $log, bool $reload = false): TransactionLog
    {
        try {
            $this->db->insert(self::tableName(), [
                'pairing_id' => $log->getPairingId(),
                'order_id' => $log->getOrderId(),
                'soap_action' => $log->getSoapAction(),
                'api_method' => $log->getApiMethod(),
                'request' => $log->getRequest(),
                'response' => $log->getResponse(),
                'soap_request' => $log->getSoapRequest(),
                'soap_response' => $log->getSoapResponse(),
                'exception_text' => $log->getExceptionText(),
            ]);

            return $reload ? $this->get($this->db->insert_id) : $log;
        } catch (Exception $e) {
            $this->logger->error('TWINT TransactionRepository::insert: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get transactions log for order
     */
    public function getByOrderId(int $orderId): array
    {
        $transactionLogTable = self::tableName();
        $pairingTable = PairingRepository::tableName();
        $query = $this->db->prepare(
            "
            SELECT l.* from {$transactionLogTable} AS l
            INNER JOIN {$pairingTable} AS p on p.id = l.pairing_id    
            WHERE l.order_id = %d 
            ORDER BY p.created_at DESC, l.created_at DESC;",
            $orderId,
        );

        $result = $this->db->get_results($query);

        return array_map(static fn ($row) => (new TransactionLog(false))->load($row), $result);
    }
}
