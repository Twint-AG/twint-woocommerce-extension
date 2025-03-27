<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class TermsAttribute implements IAttribute
{
    public function __construct($taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    public function getValue(IPackage $package)
    {
        return $package->getTerms($this->taxonomy);
    }

    private $taxonomy;
}