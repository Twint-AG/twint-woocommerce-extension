<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common\Hashing;

use Gzp\WbsNg\Common\Equality\Equality;


class OrderedHash
{
    public static function from(...$data): int
    {
        return self::start()->write(...$data)->done();
    }

    public static function start(): self
    {
        return new self();
    }

    public function write(...$data): self
    {
        foreach ($data as $datum) {

            if (is_array($datum)) {
                foreach ($datum as $k => $v) {
                    $this->write($k)->write($v);
                }
                continue;
            }

            if (is_object($datum)) {

                if ($datum instanceof Equality) {
                    $this->write($datum->hash());
                }
                else {
                    $c = get_class($datum);
                    throw new \LogicException("class does not support hashing: $c");
                }

                continue;
            }

            switch ($t = gettype($datum)) {
                case 'NULL':
                    $s = "\0";
                    break;
                case 'boolean':
                    $s = $datum ? "\1" : "\0";
                    break;
                case 'integer':
                    $s = pack('i', $datum);
                    break;
                case 'double':
                    $s = pack('f', $datum);
                    break;
                case 'string':
                    $s = $datum;
                    break;
                default:
                    throw new \LogicException("Hasher: unsupported data type '$t'");
            }

            hash_update($this->ctx, $s);
        }

        return $this;
    }

    public function done(): int
    {
        return intval(hash_final($this->ctx), 16);
    }


    private $ctx;

    private function __construct()
    {
        $this->ctx = hash_init('crc32b');
    }
}