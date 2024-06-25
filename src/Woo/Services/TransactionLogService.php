<?php

namespace Twint\Woo\Services;

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
        $request = json_encode($innovations[0]->arguments());
        $exception = $innovations[0]->exception() ?? '';
        $response = json_encode($innovations[0]->returnValue());
        $soapMessages = $innovations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        foreach ($soapMessages as $soapMessage) {
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()->body();
        }

        $soapRequests = json_encode($soapRequests);
        $soapResponses = json_encode($soapResponses);

        global $wpdb;
        $wpdb->insert(
            self::getTableName(),
            [
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'transaction_id' => $transactionId,
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