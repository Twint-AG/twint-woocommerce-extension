<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Sdk\Exception\ApiFailure;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Value\Invocation;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Repository\TransactionRepository;
use WC_Logger_Interface;

class ApiService
{
    public function __construct(
        private readonly WC_Logger_Interface   $logger,
        private readonly TransactionRepository $logRepository,
    ) {
    }

    /**
     * @param callable|null $buildLogCallback A callback function to build the log. It should accept two parameters.
     * @throws Throwable
     */
    public function call(
        InvocationRecordingClient $client,
        string                    $method,
        array                     $args,
        bool                      $save = true,
        callable                  $buildLogCallback = null
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

            $log = $this->log($returnValue ?? null, $method, $invocations, $save, $buildLogCallback);
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
        bool     $save = true,
        callable $callback = null
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
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (is_callable($callback)) {
                $log = $callback($log, $returnValue);
            }

            if (!$exception && !$save) {
                return $log;
            }

            return $this->logRepository->insert($log, true);
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
        $request = json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? null;

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

    /**
     * @throws Exception
     */
    public function saveLog(TransactionLog $log): TransactionLog
    {
        return $this->logRepository->save($log);
    }
}
