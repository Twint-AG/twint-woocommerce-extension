<?php
namespace Wbs\Migrations;

use WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IRuleMigration;


class Migration_5_2_6 implements IRuleMigration
{
    public function migrateRule($rule)
    {
        if (isset($rule['conditions']['subtotal']) && !array_key_exists('discount', $rule['conditions']['subtotal'])) {
            $rule['conditions']['subtotal']['discount'] = false;
        }

        return $rule;
    }
}

return new Migration_5_2_6();