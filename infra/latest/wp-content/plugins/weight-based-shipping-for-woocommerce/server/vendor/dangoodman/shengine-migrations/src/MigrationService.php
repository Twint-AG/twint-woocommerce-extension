<?php
namespace WbsVendors\Dgm\Shengine\Migrations;

use Dgm\PluginServices\IService;
use Dgm\PluginServices\IServiceReady;
use Dgm\Shengine\Migrations\Interfaces\Migrations\IConfigMigration;
use Dgm\Shengine\Migrations\Interfaces\Migrations\IGlobalMigration;
use Dgm\Shengine\Migrations\Interfaces\Migrations\IRuleMigration;
use Dgm\Shengine\Migrations\Interfaces\Storage\IStorageRecord;
use Dgm\WcTools\WcTools;


class MigrationService implements \WbsVendors\Dgm\PluginServices\IService, \WbsVendors\Dgm\PluginServices\IServiceReady
{
    public function __construct($currentVersion,
                                \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorageRecord $migrateFromVersion,
                                $migrationsPath,
                                \WbsVendors\Dgm\Shengine\Migrations\AbstractConfigStorage $configStorage)
    {
        $this->currentVersion = $currentVersion;
        $this->migrateFromVersion = $migrateFromVersion;
        $this->migrationsPath = $migrationsPath;
        $this->configStorage = $configStorage;
    }

    public function ready()
    {
        return $this->currentVersion !== $this->migrateFromVersion->get();
    }

    public function install()
    {
        if (did_action('plugins_loaded')) {
            $this->migrate();
        } else {
            add_action('plugins_loaded', array($this, 'migrate'), 10, 0);
        }
    }

    public function migrate($now = null)
    {
        if (!isset($now)) {
            $now = time();
        }

        $currentVersion = $this->currentVersion;

        $migrateFromVersion = (string)$this->migrateFromVersion->get();

        if ($migrateFromVersion !== '' && version_compare($migrateFromVersion, $currentVersion, '<')) {

            $migrationFiles = $this->loadSortedMigrationFiles();

            $globalMigrations = [];
            $configMigrations = [];
            foreach ($migrationFiles as $fromVersion => $file) {
                if (version_compare($migrateFromVersion, $fromVersion, '<=') && version_compare($currentVersion, $fromVersion, '>')) {
                    /** @noinspection PhpIncludeInspection */
                    $migration = include($file);
                    if ($migration instanceof \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IGlobalMigration) {
                        $globalMigrations[] = $migration;
                    }
                    if ($migration instanceof \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IRuleMigration || $migration instanceof \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IConfigMigration) {
                        $configMigrations[] = $migration;
                    }
                }
            }

            if ($globalMigrations || $configMigrations) {

                if ($globalMigrations) {
                    foreach ($globalMigrations as $migration) {
                        $migration->migrate();
                    }
                }

                if ($configMigrations) {

                    $this->configStorage->backup($migrateFromVersion, $now);

                    $self = $this;
                    $this->configStorage->forEachConfig(function ($config) use ($self, $configMigrations) {

                        foreach ($configMigrations as $migration) {

                            if ($migration instanceof \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IRuleMigration) {
                                $config = $self->configStorage->forEachRule($config, array($migration, 'migrateRule'));
                            }

                            if ($migration instanceof \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Migrations\IConfigMigration) {
                                $config = $migration->migrateConfig($config);
                            }
                        }

                        return $config;
                    });
                }

                $this->afterMigration();
            }
        }

        if ($migrateFromVersion !== $currentVersion) {
            $this->migrateFromVersion->set($currentVersion);
        }
    }


    protected function afterMigration()
    {
        // Although, in theory, we don't need to purge shipping cache since we always expect to produce
        // a similar functioning config after migrations, in practice, we'd better allow a user to test
        // a new config right after migration in case there is any issue with that rather than showing
        // results cached from a previous config.
        \WbsVendors\Dgm\WcTools\WcTools::purgeShippingCache();
    }


    private $currentVersion;
    private $migrateFromVersion;
    private $migrationsPath;
    private $configStorage;

    private function loadSortedMigrationFiles()
    {
        $files = glob($this->migrationsPath.'/*.php', GLOB_NOSORT);

        foreach ($files as $key => $file) {
            unset($files[$key]);
            $files[pathinfo($file, PATHINFO_FILENAME)] = $file;
        }

        uksort($files, 'version_compare');

        return $files;
    }
}
