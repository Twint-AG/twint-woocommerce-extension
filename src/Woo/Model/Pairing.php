<?php

declare(strict_types=1);

namespace Twint\Woo\Model;

use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;
use Twint\Woo\Constant\TwintConstant;

class Pairing extends Entity
{
    public const TIME_WINDOW_SECONDS = 10;

    public const EXPRESS_STATUS_PAID = 'PAID';

    public const EXPRESS_STATUS_CANCELLED = 'CANCELLED';

    public const EXPRESS_STATUS_MERCHANT_CANCELLED = 'MERCHANT_CANCELLED';

    protected string $id; // uuid - twint order uuid

    protected ?string $token;

    protected ?string $shippingMethodId = null;

    protected int $wcOrderId;

    protected ?string $customerData = null;

    protected bool $isExpress = false;

    protected float $amount = 0;

    protected string $status = PairingStatus::PAIRING_IN_PROGRESS;

    protected ?string $transactionStatus = null;

    protected ?string $pairingStatus = null;

    protected bool $isOrdering = false;

    protected bool $captured = false;

    protected ?string $checkedAt;

    protected ?int $checkedAgo;

    protected ?int $createdAgo;

    protected int $version = 1;

    protected string $createdAt;

    protected ?string $updatedAt;

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
            return in_array(
                $this->getStatus(),
                [self::EXPRESS_STATUS_PAID, self::EXPRESS_STATUS_CANCELLED, self::EXPRESS_STATUS_MERCHANT_CANCELLED],
                true
            );
        }

        return in_array($this->status, [OrderStatus::SUCCESS, OrderStatus::FAILURE], true);
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

    public function isSuccess(): bool
    {
        return $this->status === OrderStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === OrderStatus::FAILURE;
    }

    public function isCaptured(): bool
    {
        return $this->captured;
    }

    public function setCaptured(bool $value): void
    {
        $this->captured = $value;
    }

    public function isOrderProcessing(): bool
    {
        return $this->isOrdering;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
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

    public function getCustomerData(): array
    {
        return $this->customerData === null || $this->customerData === '' || $this->customerData === '0' ? [] : json_decode(
            $this->customerData,
            true
        );
    }

    public function setCustomerData(?string $value): self
    {
        $this->customerData = $value;
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

    public function getIsOrdering(): bool
    {
        return $this->isOrdering;
    }

    public function setIsOrdering($isOrdering): self
    {
        $this->isOrdering = $isOrdering;
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

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isMonitoring(): bool
    {
        return $this->getCheckedAt() && $this->getCheckedAgo() < self::TIME_WINDOW_SECONDS;
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

    public function getCheckedAgo(): ?int
    {
        return $this->checkedAgo ?? 0;
    }

    public function setCheckedAgo(?int $checkedAgo): self
    {
        $this->checkedAgo = $checkedAgo;
        return $this;
    }

    public function isSuccessful(): bool
    {
        if ($this->isExpress) {
            return $this->getStatus() === self::EXPRESS_STATUS_PAID;
        }

        return $this->getStatus() === OrderStatus::SUCCESS;
    }

    public function isFailure(): bool
    {
        if ($this->isExpress) {
            return $this->getPairingStatus() === PairingStatus::NO_PAIRING;
        }

        return $this->getStatus() === OrderStatus::FAILURE;
    }

    public function getPairingStatus(): ?string
    {
        return $this->pairingStatus;
    }

    public function setPairingStatus($pairingStatus): self
    {
        $this->pairingStatus = $pairingStatus;
        return $this;
    }

    public function hasDiffs(FastCheckoutCheckIn|Order $target): bool
    {
        if ($target instanceof FastCheckoutCheckIn) {
            //            dd($this->getPairingStatus() !== ($target->pairingStatus()->__toString() ?? '')
            //               , $this->getShippingMethodId() !== ($target->hasShippingMethodId() ? (string) $target->shippingMethodId() : null)
            //               , !empty($this->customerData) , $target->hasCustomerData(),
            //            $this, $target
            //            );
            return $this->getPairingStatus() !== ($target->pairingStatus()->__toString() ?? '')
                || $this->getShippingMethodId() !== ($target->hasShippingMethodId() ? (string) $target->shippingMethodId() : null)
                || (($this->customerData === null || $this->customerData === '' || $this->customerData === '0') && $target->hasCustomerData())
                || ($this->customerData !== null && $this->customerData !== '' && $this->customerData !== '0' && !$target->hasCustomerData());
        }


        /** @var Order $target */
        return $this->getPairingStatus() !== ($target->pairingStatus()?->__toString() ?? '')
            || $this->getTransactionStatus() !== $target->transactionStatus()
                ->__toString()
            || $this->getStatus() !== $target->status()
                ->__toString();
    }

    public function getShippingMethodId(): ?string
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId($shippingMethodId): self
    {
        $this->shippingMethodId = $shippingMethodId;
        return $this;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->transactionStatus;
    }

    public function setTransactionStatus($transactionStatus): self
    {
        $this->transactionStatus = $transactionStatus;
        return $this;
    }

    public function isTimedOut(): bool
    {
        return $this->getCreatedAgo() > ($this->getIsExpress() ? TwintConstant::PAIRING_TIMEOUT_EXPRESS : TwintConstant::PAIRING_TIMEOUT_REGULAR);
    }

    public function getCreatedAgo(): ?int
    {
        return $this->createdAgo ?? null;
    }

    public function setCreatedAgo(?int $value): void
    {
        $this->createdAgo = $value;
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

    protected function mapping(): array
    {
        return [
            'id' => 'id',
            'token' => 'token',
            'shipping_method_id' => 'shippingMethodId',
            'wc_order_id' => [
                'wcOrderId',
                static fn ($value) => (int) $value,
            ],
            'amount' => [
                'amount',
                static fn ($value) => (float) $value,
            ],
            'status' => 'status',
            'transaction_status' => 'transactionStatus',
            'pairing_status' => 'pairingStatus',
            'is_ordering' => [
                'isOrdering',
                static fn ($value) => (bool) $value,
            ],
            'captured' => [
                'captured',
                static fn ($value) => (bool) $value,
            ],
            'checked_at' => 'checkedAt',
            'created_at' => 'createdAt',
            'checked_ago' => [
                'checkedAgo',
                static fn ($value) => (int) $value,
            ],
            'created_ago' => [
                'createdAgo',
                static fn ($value) => (int) $value,
            ],
            'updated_at' => 'updatedAt',
            'version' => [
                'version',
                static fn ($value) => (int) $value,
            ],
            'is_express' => [
                'isExpress',
                static fn ($value) => (bool) $value,
            ],
            'customer_data' => 'customerData',
        ];
    }
}
