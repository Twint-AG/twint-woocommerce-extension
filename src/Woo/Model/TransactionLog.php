<?php

declare(strict_types=1);

namespace Twint\Woo\Model;

class TransactionLog extends Entity
{
    protected ?int $id;

    protected ?string $pairingId = null;

    protected ?int $orderId = null;

    protected string $soapAction;

    protected string $apiMethod;

    protected string $request;

    protected string $response;

    protected string $soapRequest;

    protected string $soapResponse;

    protected ?string $exceptionText;

    protected string $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    // Getter methods

    public function getPairingId(): ?string
    {
        return $this->pairingId;
    }

    public function setPairingId(string $pairingId): void
    {
        $this->pairingId = $pairingId;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getSoapAction(): string
    {
        return $this->soapAction;
    }

    public function setSoapAction($soapAction): void
    {
        $this->soapAction = $soapAction;
    }

    public function getApiMethod(): string
    {
        return $this->apiMethod;
    }

    public function setApiMethod(string $apiMethod): void
    {
        $this->apiMethod = $apiMethod;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function setRequest(string $request): void
    {
        $this->request = $request;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    // Setter methods

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    public function getSoapRequest(): string
    {
        return $this->soapRequest;
    }

    public function setSoapRequest(string $soapRequest): void
    {
        $this->soapRequest = $soapRequest;
    }

    public function getSoapResponse(): string
    {
        return $this->soapResponse;
    }

    public function setSoapResponse(string $soapResponse): void
    {
        $this->soapResponse = $soapResponse;
    }

    public function getExceptionText(): ?string
    {
        return $this->exceptionText;
    }

    public function setExceptionText(string $exceptionText): void
    {
        $this->exceptionText = $exceptionText;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    protected function mapping(): array
    {
        return [
            'id' => [
                'id',
                static fn ($value) => (int) $value,
            ],
            'pairing_id' => 'pairingId',
            'order_id' => [
                'orderId',
                static fn ($value) => (int) $value,
            ],
            'soap_action' => 'soapAction',
            'api_method' => 'apiMethod',
            'request' => 'request',
            'response' => 'response',
            'soap_request' => 'soapRequest',
            'soap_response' => 'soapResponse',
            'exception_text' => 'exceptionText',
            'created_at' => 'createdAt',
        ];
    }
}
