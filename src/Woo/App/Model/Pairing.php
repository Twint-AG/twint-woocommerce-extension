<?php

namespace Twint\Woo\App\Model;

use Twint\Sdk\Value\OrderStatus;

class Pairing
{
    protected static string $table = 'twint_pairing';

    protected string $id; // uuid - twint order uuid
    protected string $token;
    protected null|int $shippingMethodId = null;
    protected int $wcOrderId;
    protected null|int $customerId = null;
    protected null|array $customerData = null;
    protected bool $isExpress = false;
    protected float $amount = 0;
    protected string $status;
    protected string $transactionStatus;
    protected string $pairingStatus;
    protected int $isOrdering = 0;
    protected ?string $checkedAt;
    protected ?int $checkedAgo;
    protected int $version = 1;
    protected string $createdAt;
    protected string $updatedAt;

    public static function getTableName(): string
    {
        global $table_prefix;
        return $table_prefix . self::$table;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken($token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getShippingMethodId(): int|null
    {
        return $this->shippingMethodId ?? null;
    }

    public function setShippingMethodId($shippingMethodId): self
    {
        $this->shippingMethodId = $shippingMethodId;
        return $this;
    }

    public function getWcOrderId(): int
    {
        return $this->wcOrderId;
    }

    public function setWcOrderId($wcOrderId): self
    {
        $this->wcOrderId = $wcOrderId;
        return $this;
    }

    public function getCustomerId(): int|null
    {
        return $this->customerId;
    }

    public function setCustomerId($customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCustomerData(): null|array
    {
        return $this->customerData;
    }

    public function getIsExpress(): bool
    {
        return $this->isExpress;
    }

    public function setIsExpress($isExpress): self
    {
        $this->isExpress = $isExpress;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTransactionStatus(): string
    {
        return $this->transactionStatus;
    }

    public function setTransactionStatus($transactionStatus): self
    {
        $this->transactionStatus = $transactionStatus;
        return $this;
    }

    public function getPairingStatus(): string
    {
        return $this->pairingStatus;
    }

    public function setPairingStatus($pairingStatus): self
    {
        $this->pairingStatus = $pairingStatus;
        return $this;
    }

    public function getIsOrdering(): bool
    {
        return $this->isOrdering;
    }

    public function setIsOrdering($isOrdering): self
    {
        $this->isOrdering = $isOrdering;
        return $this;
    }

    public function getCheckedAt(): ?string
    {
        return $this->checkedAt ?? null;
    }

    public function setCheckedAt(?string $checkedAt): self
    {
        $this->checkedAt = $checkedAt;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt ?? date("Y-m-d H:i:s");
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt ?? date("Y-m-d H:i:s");
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function isFinished(): bool
    {
        if ($this->isExpress) {
            // TODO Update check isFinished for Express Checkout
        }

        return in_array($this->status, [OrderStatus::SUCCESS, OrderStatus::FAILURE], true);
    }

    public function isSuccess(): bool
    {
        return $this->status === OrderStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === OrderStatus::FAILURE;
    }

    public function isOrderProcessing(): bool
    {
        return (bool) $this->isOrdering;
    }

    public function getCheckedAgo(): ?int
    {
        return $this->checkedAgo ?? 0;
    }

    public function setCheckedAgo(?int $checkedAgo): self
    {
        $this->checkedAgo = $checkedAgo;
        return $this;
    }

    public function load(array $data): self
    {
        $this->setId($data['id']);
        $this->setToken($data['token'] ?? null);
        $this->setShippingMethodId($data['shipping_method_id'] ?? null);
        $this->setWcOrderId($data['wc_order_id']);
        $this->setCustomerId($data['customer_id'] ?? null);
        $this->setAmount($data['amount'] ?? 0);
        $this->setStatus($data['status']);
        $this->setTransactionStatus($data['transaction_status']);
        $this->setPairingStatus($data['pairing_status']);
        $this->setIsOrdering($data['is_ordering'] ?? 0);
        $this->setCheckedAt($data['checked_at']);
        $this->setCreatedAt($data['created_at']);
        $this->setCheckedAgo($data['checked_ago']);
        $this->setUpdatedAt($data['updated_at']);
        $this->setVersion($data['version']);

        return $this;
    }

    public function save(): bool
    {
        try {
            global $wpdb;

            $wpdb->insert(self::getTableName(), [
                'id' => $this->getId(),
                'token' => $this->getToken(),
                'shipping_method_id' => $this->getShippingMethodId(),
                'wc_order_id' => $this->getWcOrderId(),
                'customer_id' => $this->getCustomerId(),
                'customer_data' => $this->getCustomerData(),
                'is_express' => $this->getIsExpress(),
                'amount' => $this->getAmount(),
                'status' => $this->getStatus(),
                'transaction_status' => $this->getTransactionStatus(),
                'pairing_status' => $this->getPairingStatus(),
                'is_ordering' => $this->getIsOrdering(),
                'checked_at' => $this->getCheckedAt(),
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
            ]);

            return true;
        } catch (\Exception $e) {
            // TODO LOG Handler
            return false;
        }
    }
}