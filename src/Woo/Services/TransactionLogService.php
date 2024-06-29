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
        $apiMethod = $innovations[0]->methodName() ?? ' ';
        $soapActions = [];
        foreach ($soapMessages as $soapMessage) {
            $soapActions[] = $soapMessage->request()->action();
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()->body();
        }

        $soapRequests = json_encode($soapRequests);
        $soapResponses = json_encode($soapResponses);
        $soapActions = json_encode($soapActions);

        if (!$this->checkDuplicatedTransactionLogInLastTime($orderId)) {
            global $wpdb;
            $wpdb->insert(
                self::getTableName(),
                [
                    'order_id' => $orderId,
                    'order_status' => wc_get_order_status_name($orderStatus),
                    'transaction_id' => $transactionId,
                    'api_method' => $apiMethod,
                    'soap_action' => $soapActions,
                    'request' => $request,
                    'response' => $response,
                    'soap_request' => $soapRequests,
                    'soap_response' => $soapResponses,
                    'exception_text' => $exception,
                    'created_at' => date("Y-m-d H:i:s"),
                ],
            );
        }
    }

    public function checkDuplicatedTransactionLogInLastTime($orderId): bool
    {
        // Last time calculated by minutes
        $lastTime = get_option('twint_transaction_log_last_time', 1);
        $time = strtotime("-{$lastTime} minutes");
        $time = date('Y-m-d H:i:s', $time);
        $currentTime = date('Y-m-d H:i:s');
        global $wpdb;
        $sql = "SELECT record_id FROM wp_twint_transactions_log WHERE order_id = " . $orderId . " AND created_at BETWEEN '" . $time . "' AND '" . $currentTime . "'";
        $result = $wpdb->get_results($sql);
        return !empty($result);
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

    public function getLogTransactionDetails(int $recordId): array
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * from `" . self::getTableName() . "` WHERE record_id = $recordId;");

        return (array)$result[0];
    }
}