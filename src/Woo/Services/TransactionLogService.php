<?php

namespace Twint\Woo\Services;

use Twint\Sdk\Exception\ApiFailure;

class TransactionLogService
{
    public static function getTableName(): string
    {
        global $table_prefix;
        return $table_prefix . 'twint_transactions_log';
    }

    public function writeObjectLog(
        string $orderId,
        string $orderStatus,
        string $transactionId,
        array  $innovations
    ): void
    {
        if ($innovations === []) {
            return;
        }

        $request = json_encode($innovations[0]->arguments());
        $exception = $innovations[0]->exception() ?? '';

        if ($exception instanceof ApiFailure) {
            $exception = $exception->getMessage();
        }

        $response = json_encode($innovations[0]->returnValue());
        $soapMessages = $innovations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        $apiMethods = [];
        foreach ($soapMessages as $soapMessage) {
            $apiMethods[] = $soapMessage->request()->action();
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()->body();
        }

        $soapRequests = json_encode($soapRequests);
        $soapResponses = json_encode($soapResponses);
        $apiMethods = json_encode($apiMethods);

        global $wpdb;
        $wpdb->insert(
            self::getTableName(),
            [
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'transaction_id' => $transactionId,
                'api_method' => $apiMethods,
                'request' => $request,
                'response' => $response,
                'soap_request' => $soapRequests,
                'soap_response' => $soapResponses,
                'exception_text' => $exception,
                'created_at' => time(),
            ],
        );
    }

    /**
     * Get transactions log for order
     * @param int $orderId
     * @return array
     */
    public function getLogTransactions(int $orderId): array
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * from `" . self::getTableName() . "` WHERE order_id = $orderId;");
        $ret = [];
        foreach ($result as $row) {
            $ret[] = (array)$row;
        }

        return $ret;
    }
}