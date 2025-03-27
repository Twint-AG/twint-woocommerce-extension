<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Config;

use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\Exceptions\InvalidType;
use Gzp\WbsNg\Mapping\T;
use GzpWbsNgVendors\Dgm\Shengine\Model\Destination;


/**
 * @immutable
 */
class DestCond
{
    use DestCondMapping;


    public function __construct(bool $include, $tree)
    {
        $this->include = $include;
        $this->tree = $tree;
    }

    public function match(?Destination $dest): bool
    {
        $path = $this->getLocationPath($dest);
        $path[] = null;

        $tree = $this->tree;
        foreach ($path as $seg) {

            if ($tree === 'all') {
                return $this->include;
            }

            if (!$tree) {
                return !$this->include;
            }

            if (!$seg) {
                return false;
            }

            $tree = $tree[$seg] ?? null;
        }

        throw new \LogicException('unexpected loop exit');
    }

    protected function getLocationPath(?Destination $dest): array
    {
        if (!$dest) {
            return [];
        }

        $segs = [
            //WC()->countries->get_continent_code_for_country($dest->getCountry()),
            $dest->getCountry(),
            $dest->getState(),
        ];

        $path = [];
        foreach ($segs as $seg) {
            if (!$seg) break;
            $path[] = $seg;
        }

        return $path;
    }


    /**
     * @var bool
     */
    private $include;

    /**
     * @psalm-type Tree = "all"|array<string, Tree>
     * @var Tree
     */
    private $tree;
}


trait DestCondMapping
{
    public static function unserialize(?array $data): self
    {
        if (!isset($data)) {
            if (!isset(self::$ANY)) {
                self::$ANY = new self(true, 'all');
            }
            return self::$ANY;
        }

        $data = Context::of($data);

        $include = $data['include']->map([T::class, 'bool']);

        $tree = $data['tree']->map(function($v) {
            if ($v !== 'all' && !is_array($v)) {
                throw new InvalidType('"all"|array', gettype($v));
            }
            return $v;
        });

        return new self($include, $tree);
    }

    /**
     * @var self
     */
    private static $ANY;
}