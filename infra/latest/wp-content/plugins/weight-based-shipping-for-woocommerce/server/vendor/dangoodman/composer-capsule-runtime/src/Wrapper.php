<?php
namespace WbsVendors\Dgm\ComposerCapsule\Runtime;


class Wrapper
{
    const REFKIND_CLASS = 'class';
    const REFKIND_FUNC  = 'func';
    const REFKIND_CONST = 'const';


    public function __construct(array $wrap, array $unwrap)
    {
        $this->wrap = $wrap;
        $this->unwrap = $unwrap;
    }

    /**
     * Wraps a known reference with the root namespace. Unknown references aren't changed.
     *
     * @param string $reference
     * @param string $kind
     * @return bool  True if the reference has been prefixed
     */
    public function wrap(&$reference, $kind)
    {
        $bucket = $this->wrap[$kind];

        if (isset($bucket[$reference])) {
            $reference = $bucket[$reference];
            return true;
        }

        return false;
    }

    public function unwrap(&$reference, $kind)
    {
        $bucket = $this->unwrap[$kind];

        if (isset($bucket[$reference])) {
            $reference = $bucket[$reference];
            return true;
        }

        return false;
    }


    private $wrap;
    private $unwrap;
}