<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Monitor;

use JetBrains\PhpStorm\ArrayShape;
use Twint\Woo\Model\Pairing;

class MonitoringStatus{

    const STATUS_PAID = 'PAID';
    const STATUS_CANCELLED = 'FAILED';
    const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    private bool $finish;
    private string $status;

    /**
     * Extra information for specifics status
     *
     * @var array
     */
    private array $extra = [];

    public function __construct()
    {
    }

    public static function fromPairing(Pairing $pairing): MonitoringStatus
    {
        $instance = new self();
        $instance->status = self::extractStatus($pairing);
        $instance->finish = in_array($instance->status, [self::STATUS_PAID, self::STATUS_CANCELLED], true);

        return $instance;
    }

    public function addExtra(string $key, mixed $value):void{
        $this->extra[$key] = $value;
    }

    private static function extractStatus(Pairing $pairing): string
    {
        if ($pairing->isSuccessful()) {
            return self::STATUS_PAID;
        }
        if ($pairing->isFailure()) {
            return self::STATUS_CANCELLED;
        }

        return self::STATUS_IN_PROGRESS;
    }

    #[ArrayShape(['finish' => "bool", 'status' => "string", 'extra' => "array"])]
    public function toArray(): array
    {
        return [
            'finish' => $this->finish,
            'status' => $this->status,
            'extra' => $this->extra
        ];
    }
}
