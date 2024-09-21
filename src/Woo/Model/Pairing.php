<?php

declare(strict_types=1);

namespace Twint\Woo\Model;

use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;
use Twint\Woo\Constant\TwintConstant;

class Pairing
{
    public const TIME_WINDOW_SECONDS = 10;

    public const EXPRESS_STATUS_PAID = 'PAID';

    public const EXPRESS_STATUS_CANCELLED = 'CANCELLED';

    public const EXPRESS_STATUS_MERCHANT_CANCELLED = 'MERCHANT_CANCELLED';

    protected string $id; // uuid - twint order uuid

    protected string $token;

    protected null|int $shippingMethodId = null;

    protected int $wcOrderId;

    protected null|int $customerId = null;

    protected null|array $customerData = null;

    protected bool $isExpress = false;

    protected float $amount = 0;

    protected string $status = PairingStatus::PAIRING_IN_PROGRESS;

    protected string $transactionStatus = '';

    protected string $pairingStatus;

    protected int $isOrdering = 0;

    protected ?string $checkedAt;

    protected ?int $checkedAgo;

    protected ?int $createdAgo;

    protected int $version = 1;

    protected string $createdAt;

    protected string $updatedAt;

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

    public function getCreatedAgo(): ?int
    {
        return $this->createdAgo ?? null;
    }

    public function setCheckedAgo(?int $checkedAgo): self
    {
        $this->checkedAgo = $checkedAgo;
        return $this;
    }

    public function setCreatedAgo(?int $value): void
    {
        $this->createdAgo = $value;
    }

    public function load(array $data): self
    {
        $this->setId($data['id']);
        $this->setToken($data['token'] ?? null);
        $this->setShippingMethodId($data['shipping_method_id'] ?? null);
        $this->setWcOrderId((int) $data['wc_order_id']);
        $this->setCustomerId($data['customer_id'] ?? null);
        $this->setAmount((float) $data['amount'] ?? 0);
        $this->setStatus($data['status']);
        $this->setTransactionStatus($data['transaction_status']);
        $this->setPairingStatus($data['pairing_status']);
        $this->setIsOrdering((int) $data['is_ordering'] ?? 0);
        $this->setCheckedAt($data['checked_at']);
        $this->setCreatedAt($data['created_at']);
        $this->setCheckedAgo((int) $data['checked_ago']);
        $this->setCheckedAgo((int) $data['created_ago']);
        $this->setUpdatedAt($data['updated_at']);
        $this->setVersion((int) $data['version']);

        return $this;
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

    public function getIsOrdering(): int
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
        return $this->createdAt ?? date('Y-m-d H:i:s');
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt ?? date('Y-m-d H:i:s');
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isMonitoring(): bool
    {
        return $this->getCheckedAt() && $this->getCheckedAgo() < self::TIME_WINDOW_SECONDS;
    }

    public function isSuccessful(): bool
    {
        return $this->getStatus() === OrderStatus::SUCCESS;
    }

    public function isFailure(): bool
    {
        return $this->getStatus() === OrderStatus::FAILURE;
    }

    public function hasDiffs(FastCheckoutCheckIn|Order $target): bool
    {
        if ($target instanceof FastCheckoutCheckIn) {
            return $this->getPairingStatus() !== ($target->pairingStatus()->__toString() ?? '')
                || $this->getShippingMethodId() !== ($target->hasShippingMethodId() ? (string) $target->shippingMethodId() : null);
        }


        /** @var Order $target */
        return $this->getPairingStatus() !== ($target->pairingStatus()?->__toString() ?? '')
            || $this->getTransactionStatus() !== $target->transactionStatus()
                ->__toString()
            || $this->getStatus() !== $target->status()
                ->__toString();
    }

    public function isTimedOut(): bool
    {
        return $this->getCreatedAgo() > ($this->getIsExpress() ? TwintConstant::PAIRING_TIMEOUT_EXPRESS : TwintConstant::PAIRING_TIMEOUT_REGULAR);
    }
}
