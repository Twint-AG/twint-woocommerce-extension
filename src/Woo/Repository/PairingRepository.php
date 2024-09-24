<?php

declare(strict_types=1);

namespace Twint\Woo\Repository;

use Exception;
use mysqli_result;
use Throwable;
use Twint\Woo\Exception\DatabaseException;
use Twint\Woo\Model\Pairing;
use WC_Logger_Interface;
use wpdb;

class PairingRepository
{
    public function __construct(
        private readonly wpdb $db,
        private readonly WC_Logger_Interface $logger,
    ) {
    }

    public static function tableName(): string
    {
        global $table_prefix;

        return $table_prefix . 'twint_pairing';
    }

    /**
     * @throws Throwable
     */
    public function save(Pairing $pairing): Pairing
    {
        if ($pairing->isNewRecord()) {
            return $this->insert($pairing);
        }

        return $this->update($pairing);
    }

    public function insert(Pairing $pairing): Pairing
    {
        try {
            $this->db->insert(self::tableName(), [
                'id' => $pairing->getId(),
                'token' => $pairing->getToken(),
                'shipping_method_id' => $pairing->getShippingMethodId(),
                'wc_order_id' => $pairing->getWcOrderId(),
                'customer_data' => $pairing->getCustomerData(),
                'is_express' => $pairing->getIsExpress(),
                'amount' => $pairing->getAmount(),
                'status' => $pairing->getStatus(),
                'transaction_status' => $pairing->getTransactionStatus(),
                'pairing_status' => $pairing->getPairingStatus(),
                'is_ordering' => $pairing->getIsOrdering(),
                'checked_at' => $pairing->getCheckedAt(),
                'created_at' => $pairing->getCreatedAt(),
                'captured' => $pairing->isCaptured(),
            ]);

            return $this->get($pairing->getId());
        } catch (Exception $e) {
            $this->logger->error('TWINT PairingRepository::insert: ' . $e->getMessage());
        }
    }

    public function get(mixed $id): ?Pairing
    {
        $select = $this->getSelect();

        $query = $this->db->prepare("SELECT {$select} FROM %i WHERE id = %s LIMIT 1;", [self::tableName(), $id]);

        return ($result = $this->db->get_results($query)) ? (new Pairing(false))->load((array) reset($result)) : null;
    }

    private function getSelect(): string
    {
        return '*, 
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(checked_at)) AS checked_ago,
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(created_at)) AS created_ago 
               ';
    }

    /**
     * @throws Exception|Throwable
     */
    public function update(Pairing $pairing): Pairing
    {
        try {
            $customerData = $pairing->getCustomerData();

            $result = $this->db->update(self::tableName(), [
                'version' => $pairing->getVersion(),
                'token' => $pairing->getToken(),
                'shipping_method_id' => $pairing->getShippingMethodId(),
                'wc_order_id' => $pairing->getWcOrderId(),
                'customer_data' => $customerData === [] ? null : json_encode($customerData),
                'is_express' => $pairing->getIsExpress(),
                'amount' => $pairing->getAmount(),
                'status' => $pairing->getStatus(),
                'transaction_status' => $pairing->getTransactionStatus(),
                'pairing_status' => $pairing->getPairingStatus(),
                'is_ordering' => $pairing->getIsOrdering(),
                'checked_at' => $pairing->getCheckedAt(),
                'created_at' => $pairing->getCreatedAt(),
                'captured' => $pairing->isCaptured(),
            ], [
                'id' => $pairing->getId(),
            ]);

            if(!$result){
                throw new DatabaseException($this->db->last_error);
            }

            return $this->get($pairing->getId());
        }
        catch (Throwable $e) {
            !($e instanceof  DatabaseException) && $this->logger->error('TWINT PairingRepository::update: ' . $e->getMessage());

            throw $e;
        }
    }

    public function updateCheckedAt(Pairing $pairing): mysqli_result|bool|int|null
    {
        $query = $this->db->prepare('UPDATE %i SET checked_at = NOW() WHERE id = %s;', [
            self::tableName(),
            $pairing->getId(),
        ]);

        return $this->db->query($query);
    }

    public function loadInProcessPairings(): array
    {
        $select = $this->getSelect();

        $query = $this->db->prepare("SELECT {$select} FROM %i WHERE status IN ( %s ) ORDER BY created_at ASC;", [
            self::tableName(),
            implode(',', ['PAIRING_IN_PROGRESS', 'IN_PROGRESS']),
        ]);

        $results = $this->db->get_results($query);
        $pairings = [];
        foreach ($results as $result) {
            $pairings[] = (new Pairing(false))->load((array) $result);
        }

        return $pairings;
    }

    public function findByWooOrderId(int $orderId): ?Pairing
    {
        $select = $this->getSelect();
        $query = $this->db->prepare("SELECT {$select} FROM %i WHERE wc_order_id = %d LIMIT 1;", [
            self::tableName(),
            $orderId,
        ]);

        $result = $this->db->get_results($query);
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array) reset($result));
    }

    public function markAsOrdering(string $id): mysqli_result|bool|int|null
    {
        $query = $this->db->prepare('UPDATE %i SET is_ordering = 1 WHERE id = %s;', [self::tableName(), $id]);

        return $this->db->query($query);
    }

    public function markAsCancelled(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_CANCELLED);
    }

    private function updateStatus(string $id, string $status): mysqli_result|bool|int|null
    {
        $query = $this->db->prepare('UPDATE %i SET status = %s WHERE id = %s;', [self::tableName(), $status, $id]);

        return $this->db->query($query);
    }

    public function markAsPaid(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_PAID);
    }

    public function markAsMerchantCancelled(string $id): mysqli_result|bool|int|null
    {
        return $this->updateStatus($id, Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED);
    }
}
