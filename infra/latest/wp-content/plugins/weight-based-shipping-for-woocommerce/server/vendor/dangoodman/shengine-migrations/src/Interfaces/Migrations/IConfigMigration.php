<?php
namespace WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations;


interface IConfigMigration
{
    /**
     * @param array $config
     * @return array
     */
    function migrateConfig(array $config);
}