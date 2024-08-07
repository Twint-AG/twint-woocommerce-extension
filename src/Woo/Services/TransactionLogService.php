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
        try {
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
            $apiMethod = $innovations[0]->methodName() ?? 'unknown';
            $soapActions = [];
            foreach ($soapMessages as $soapMessage) {
                $soapActions[] = $soapMessage->request()->action();
                $soapRequests[] = $soapMessage->request()->body();
                $soapResponses[] = $soapMessage->response()->body();
            }

            $soapRequests = json_encode($soapRequests);
            $soapResponses = json_encode($soapResponses);
            $soapActions = json_encode($soapActions);

            $data = [
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'transaction_id' => $transactionId,
                'api_method' => $apiMethod,
                'soap_action' => $soapActions,
                'request' => $request,
                'response' => $response,
                'soap_request' => $soapRequests,
                'soap_response' => $soapResponses,
                'exception_text' => $exception,
                'created_at' => date("Y-m-d H:i:s"),
            ];

            if (!$this->checkDuplicatedTransactionLog($data)) {
                global $wpdb;
                $wpdb->insert(self::getTableName(), $data);
            }
        } catch (\Exception $exception) {
            wc_get_logger()->error('error_log_transaction' . PHP_EOL . $exception->getMessage());
        }
    }

    public function checkDuplicatedTransactionLog(array $record): bool
    {
        global $wpdb;
        $sql = "SELECT record_id FROM " . self::getTableName() . " 
                WHERE order_id = " . $record['order_id'] . " 
                AND api_method = '" . $record['api_method'] . "' 
                AND order_status = '" . $record['order_status'] . "'";
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