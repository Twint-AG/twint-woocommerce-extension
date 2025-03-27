<?php
namespace Wbs\Migrations;

use WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IGlobalMigration;


/** @noinspection AutoloadingIssuesInspection */
class Migration_5_3_27 implements IGlobalMigration
{
    public function migrate()
    {
        self::preserveOldBehaviorForExistingInstallations();
    }

    public static function preserveOldBehaviorForExistingInstallations()
    {
        $settings = get_option($option = 'wbs_settings', null);
        if (isset($settings)) {
            return;
        }

        update_option($option, [
            'preferCustomPackagePrice' => false,
            'includeNonShippableItems' => false,
        ]);
    }
}

return new Migration_5_3_27();