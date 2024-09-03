<?php

namespace Twint\Woo\Services;

use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Abstract\Core\Model\ApiResponse;
use Twint\Woo\Abstract\ServiceProvider\DatabaseServiceProvider;
use Twint\Woo\App\API\ApiService;
use Twint\Woo\App\Model\Pairing;
use Twint\Woo\Factory\ClientBuilder;
use WC_Order;

class PairingService
{
    private ClientBuilder $client;
    private ApiService $apiService;

    public function __construct()
    {
        $this->client = new ClientBuilder();
        $this->apiService = new ApiService();
    }

    public function create(ApiResponse $response, WC_Order $order): Pairing
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();

        $pairing = new Pairing();

        $pairing->setToken($tOrder->pairingToken()?->__toString());
        $pairing->setAmount($tOrder->amount()->amount());
        $pairing->setWcOrderId($order->get_id());
        $pairing->setId($tOrder->id()->__toString());
        $pairing->setTransactionStatus($tOrder->transactionStatus()->__toString());
        $pairing->setPairingStatus($tOrder->pairingStatus()->__toString());
        $pairing->setStatus($tOrder->status()->__toString());

        $pairing->save();

        return $pairing;
    }

    /**
     * @throws \Throwable
     */
    public function monitor(Pairing $pairing): bool
    {
        if ($pairing->isFinished()) {
            return true;
        }

        $org = clone $pairing;

        $client = $this->client->build();
        $apiResponse = $this->apiService->call($client, 'monitorOrder', [
            new OrderId(new Uuid($pairing->getId()))
        ], false);

        /** @var Order $tOrder */
        $tOrder = $apiResponse->getReturn();
        if ($pairing->getStatus() !== $tOrder->status()->__toString() ||
            $pairing->getPairingStatus() !== $tOrder->pairingStatus()?->__toString() ||
            $pairing->getTransactionStatus() !== $tOrder->transactionStatus()
                ->__toString()
        ) {
            try {
                $this->update($pairing, $apiResponse);
            } catch (\mysqli_sql_exception|\Exception $e) {
                if ($e->getSQLState() !== DatabaseServiceProvider::DATABASE_TRIGGER_STATE_ERROR_CODE) {
                    throw $e;
                }

                wc_get_logger()->info(
                    "[TWINT] - Update pairing is locked {$pairing->getId()} {$pairing->getVersion()} {$pairing->getStatus()}"
                );

                return false;
            }
        }

        if ($tOrder->isPending()) {
            return false;
        }

        // Get WC Order
        $order = wc_get_order($pairing->getWcOrderId());

        if (!$org->isSuccess() && $tOrder->isSuccessful()) {
            $order->update_status(\WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPaid());
        }

        if (!$org->isFailed() && $tOrder->isFailure()) {
            $order->update_status(\WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterCancelled());
        }

        $this->updateLog($apiResponse->getLog(), $pairing, $order);

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function updateLog(array $log, Pairing $pairing, WC_Order $order): void
    {
        $log['pairing_id'] = $pairing->getId();
        $log['order_id'] = $order->get_id();
        $log['order_status'] = $order->get_status();
        $log['transaction_id'] = $order->get_transaction_id();

        $this->apiService->saveLog($log);
    }

    protected function update(Pairing $pairing, ApiResponse $response): int|bool|null|\mysqli_result
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();
        global $wpdb;
        $newPairing = new Pairing();
        return $wpdb->update($newPairing->getTableName(), [
            'version' => $pairing->getVersion(),
            'status' => $tOrder->status()
                ->__toString(),
            'pairing_status' => $tOrder->pairingStatus()?->__toString(),
            'transaction_status' => $tOrder->transactionStatus()
                ->__toString(),
            'updated_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $pairing->getId()
        ]);
    }

    public function loadInProcessPairings(): array
    {
        global $wpdb;
        $table = Pairing::getTableName();
        $results = $wpdb->get_results("SELECT * FROM {$table} WHERE status IN ('PAIRING_IN_PROGRESS', 'IN_PROGRESS') ORDER BY created_at ASC");
        $pairings = [];
        foreach ($results as $result) {
            $instance = new Pairing();
            $result = (array)$result;
            $pairings[] = $instance->load($result);
        }

        return $pairings;
    }

    public function findByWooOrderId($orderId): ?Pairing
    {
        global $wpdb;
        $table = Pairing::getTableName();
        $result = $wpdb->get_results("SELECT * FROM {$table} WHERE wc_order_id = {$orderId} LIMIT 1");
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array)reset($result));
    }

    public function findById(mixed $pairingId): ?Pairing
    {
        global $wpdb;
        $table = Pairing::getTableName();
        $result = $wpdb->get_results("SELECT * FROM {$table} WHERE id = '{$pairingId}' LIMIT 1");
        if (empty($result)) {
            return null;
        }

        $instance = new Pairing();
        return $instance->load((array)reset($result)) ?? null;
    }
}