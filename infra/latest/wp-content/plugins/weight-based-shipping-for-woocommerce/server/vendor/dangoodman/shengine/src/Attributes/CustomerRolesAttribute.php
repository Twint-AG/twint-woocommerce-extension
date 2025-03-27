<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IAttribute;
use Dgm\Shengine\Interfaces\IPackage;


class CustomerRolesAttribute implements \WbsVendors\Dgm\Shengine\Interfaces\IAttribute
{
    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $roles = array();

        if ($customer = $package->getCustomer())
        if ($customerId = $customer->getId())
        if ($wpuser = get_userdata($customerId)) {
            $roles = $wpuser->roles;
        }

        return $roles;
    }
}