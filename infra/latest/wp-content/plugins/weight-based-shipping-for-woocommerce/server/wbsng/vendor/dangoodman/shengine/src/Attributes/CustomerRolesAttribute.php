<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class CustomerRolesAttribute implements IAttribute
{
    public function getValue(IPackage $package)
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