<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Config\Document;


use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;


class Settings
{
    public $disableSplitShipping;

    public function __construct(bool $disableSplitShipping = false)
    {
        $this->disableSplitShipping = $disableSplitShipping;
    }

    public static function unserialize(?array $data): self
    {
        $self = new self();

        if (!isset($data)) {
            return $self;
        }

        $data = Context::of($data);

        $self->disableSplitShipping = $data['disableSplitShipping']->map([T::class, 'bool']);

        return $self;
    }
}