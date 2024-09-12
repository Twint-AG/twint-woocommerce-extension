<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Throwable;
use Twint\Sdk\Exception\ApiFailure;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Value\Invocation;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Repository\TransactionRepository;

class ApiService
{
    /**
     * @param callable|null $buildLogCallback A callback function to build the log. It should accept two parameters.
     * @throws Throwable
     */
    public function call(InvocationRecordingClient $client, string $method, array $args, bool $save = true, callable $buildLogCallback = null): ApiResponse
    {
        try {
            $returnValue = $client->{$method}(...$args);
        } catch (Throwable $e) {
            wc_get_logger()->error('TWINT - API error: ' . \PHP_EOL . $e->getMessage());
            throw $e;
        } finally {
            $invocations = $client->flushInvocations();

            $log = $this->log($returnValue ?? null, $method, $invocations, $save, $buildLogCallback);
        }

        return new ApiResponse($returnValue ?? null, $log);
    }

    /**
     * @param Invocation[] $invocation
     */
    protected function log(
        mixed    $returnValue,
        string   $method,
        array    $invocation,
        bool     $save = true,
        callable $callback = null
    ): array
    {
        $log = [];

        try {
            list($request, $response, $soapRequests, $soapResponses, $soapActions, $exception) = $this->parse(
                $invocation
            );

            $log = [
                'api_method' => $method,
                'soap_action' => $soapActions,
                'request' => $request,
                'response' => $response,
                'soap_request' => $soapRequests,
                'soap_response' => $soapResponses,
                'exception_text' => $exception,
                'created_at' => date("Y-m-d H:i:s"),
            ];

            if (is_callable($callback)) {
                $log = $callback($log, $returnValue);
            }

            if (!empty($exception) && !$save) {
                return $log;
            }

            global $wpdb;
            $wpdb->insert(TransactionRepository::getTableName(), $log);
        } catch (Throwable $exception) {
            wc_get_logger()->error("TWINT - Error when saving setting " . PHP_EOL . $exception->getMessage());
        }

        return $log;
    }

    /**
     * @param Invocation[] $invocations
     */
    protected function parse(array $invocations): array
    {
        $request = json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? ' ';

        if ($exception instanceof ApiFailure) {
            $exception = $exception->getMessage();
        }

        $response = json_encode($invocations[0]->returnValue());
        $soapMessages = $invocations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        $soapActions = [];

        foreach ($soapMessages as $soapMessage) {
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()?->body();
            $soapActions[] = $soapMessage->request()->action();
        }

        $soapRequests = json_encode($soapRequests);
        $soapResponses = json_encode($soapResponses);
        $soapActions = json_encode($soapActions);

        return [$request, $response, $soapRequests, $soapResponses, $soapActions, $exception];
    }

    public function saveLog(array $log): void
    {
        global $wpdb;
        if (isset($log['id'])) {
            // Update transaction log
            $wpdb->update(TransactionRepository::getTableName(), $log, [
                'id' => $log['id'],
            ]);
        }

        // Create transaction log
        $wpdb->insert(TransactionRepository::getTableName(), $log);
    }
}
