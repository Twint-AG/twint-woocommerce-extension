<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IAttribute;
use Dgm\Shengine\Interfaces\IPackage;


class TermsAttribute implements \WbsVendors\Dgm\Shengine\Interfaces\IAttribute
{
    public function __construct($taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return $package->getTerms($this->taxonomy);
    }

    private $taxonomy;
}