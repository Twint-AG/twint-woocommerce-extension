<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Config\Method;

use Gzp\WbsNg\Mapping\Context;


class Settings
{
    /**
     * @var PriceSettings
     */
    public $price;


    public function __construct(PriceSettings $price = null)
    {
        $this->price = $price ?? new PriceSettings();
    }

    public static function unserialize(?array $data): self
    {
        if (!isset($data)) {
            return new self();
        }

        $data = Context::of($data);

        $price = $data['price']->map([PriceSettings::class, 'unserialize']);

        return new self($price);
    }
}