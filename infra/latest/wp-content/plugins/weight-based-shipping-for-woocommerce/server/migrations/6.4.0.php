<?php
namespace Wbs\Migrations;

use WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IGlobalMigration;


/** @noinspection PhpUnused */
class Migration_6_4_0 implements IGlobalMigration
{
    public function migrate()
    {
        self::keepLegacyGlobalMethodVisibleIfNotEmpty();
    }

    public static function keepLegacyGlobalMethodVisibleIfNotEmpty()
    {
        $globalMethodsSwitch = 'wbs_global_methods';
        if (get_option($globalMethodsSwitch, null) !== null) {
            return;
        }

        $config = get_option('wbs_config', null);
        if (!empty($config['rules'])) {
            update_option($globalMethodsSwitch, 'only-wbs');
        }
    }
}

return new Migration_6_4_0();