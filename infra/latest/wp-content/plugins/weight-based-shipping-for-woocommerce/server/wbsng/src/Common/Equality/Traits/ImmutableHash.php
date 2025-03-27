<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common\Equality\Traits;


trait ImmutableHash
{
    public function hash(): int
    {
        if (!isset($this->hash)) {
            $this->hash = $this->_hash();
        }
        return $this->hash;
    }

    abstract protected function _hash(): int;

    /**
     * @var int
     */
    private $hash;
}