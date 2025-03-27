<?php
namespace Wbs\Migrations;

use WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IGlobalMigration;


/** @noinspection PhpUnused */
class Migration_5_11_0 implements IGlobalMigration
{
    public function migrate()
    {
        self::hideLegacyGlobalMethodIfEmpty();
    }

    public static function hideLegacyGlobalMethodIfEmpty()
    {
        $globalMethodsSwitch = 'wbs_global_methods';
        if (get_option($globalMethodsSwitch, null) !== null) {
            return;
        }

        $config = get_option('wbs_config', null);
        if (!empty($config['rules'])) {
            return;
        }

        update_option($globalMethodsSwitch, 'only-wbsng');
    }
}

return new Migration_5_11_0();