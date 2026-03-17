<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Value\Invocation;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Repository\TransactionRepository;
use WC_Logger_Interface;

/**
 * @method TransactionRepository getLogRepository()
 */
class ApiService
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['logRepository'];

    public function __construct(
        private readonly WC_Logger_Interface        $logger,
        private readonly TransactionRepository|Lazy $logRepository,
    ) {
    }

    /**
     * @param callable $buildLogCallback A callback function to build the log. It should accept two parameters.
     * @throws Throwable
     */
    public function call(
        InvocationRecordingClient $client,
        string                    $method,
        array                     $args,
        callable                  $buildLogCallback,
        bool                      $save = true,
    ): ApiResponse {
        if (!in_array($method, ['monitorOrder', 'monitorFastCheckOutCheckIn'], true)) {
            $save = true;
        }

        try {
            $returnValue = $client->{$method}(...$args);
        } catch (Throwable $e) {
            $this->logger->error('TWINT ApiService::call: ' . $method . ' ' . $e->getMessage());
            throw $e;
        } finally {
            $invocations = $client->flushInvocations();

            $log = $this->log($returnValue ?? null, $method, $invocations, $buildLogCallback, $save);
        }

        return new ApiResponse($returnValue ?? null, $log);
    }

    /**
     * @param Invocation[] $invocation
     * @throws Throwable
     */
    protected function log(
        mixed    $returnValue,
        string   $method,
        array    $invocation,
        callable $callback,
        bool     $save = true,
    ): TransactionLog {
        try {
            list($request, $response, $soapRequests, $soapResponses, $soapActions, $exception) = $this->parse(
                $invocation
            );

            $log = new TransactionLog();
            $log->load([
                'api_method' => $method,
                'soap_action' => $soapActions,
                'request' => $request,
                'response' => $response,
                'soap_request' => $soapRequests,
                'soap_response' => $soapResponses,
                'exception_text' => $exception,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $log = $callback($log, $returnValue);

            if (!$exception && !$save) {
                return $log;
            }

            return $this->getLogRepository()->insert($log, true);
        } catch (Throwable $e) {
            $this->logger->error('TWINT ApiService::log: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param Invocation[] $invocations
     */
    protected function parse(array $invocations): array
    {
        $request = wp_json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? null;

        if ($exception instanceof Throwable) {
            $exception = $exception->getMessage();
        }

        $response = wp_json_encode($invocations[0]->returnValue());
        $soapMessages = $invocations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        $soapActions = [];

        foreach ($soapMessages as $soapMessage) {
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()?->body();
            $soapActions[] = $soapMessage->request()->action();
        }

        $soapRequests = wp_json_encode($soapRequests);
        $soapResponses = wp_json_encode($soapResponses);
        $soapActions = wp_json_encode($soapActions);

        return [$request, $response, $soapRequests, $soapResponses, $soapActions, $exception];
    }

    /**
     * @throws Exception
     */
    public function saveLog(TransactionLog $log): TransactionLog
    {
        return $this->getLogRepository()->save($log);
    }
}
