<?php
namespace WbsVendors\Dgm\Shengine\Migrations;

use Dgm\Shengine\Migrations\Interfaces\Storage\IStorage;


abstract class AbstractConfigStorage
{
    public function __construct($sqlLikePattern, \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorage $storage)
    {
        $this->sqlLikePattern = $sqlLikePattern;
        $this->storage = $storage;
    }

    public function backup($configVersion, $backupTime)
    {
        foreach ($this->findConfigKeys() as $key) {

            $bkpKey = null; {
                $idx = 1;
                do {
                    $bkpKey = "{$key}{$this->bkpMarker}{$configVersion}_{$idx}";
                    $idx++;
                } while ($this->storage->get($bkpKey, false) !== false);
            }

            $this->storage->set($bkpKey, json_encode(array(
                'time' => $backupTime,
                'time_utc' => gmdate('Y-m-d H:i:s', $backupTime),
                'config' => $this->storage->get($key),
            )), false);
        }
    }

    public function forEachConfig($callback)
    {
        foreach ($this->findConfigKeys() as $key) {
            $config = $this->read($key);
            $config = $callback($config);
            $this->write($key, $config);
        }
    }

    public abstract function forEachRule($fromConfig, $callback);


    protected function read($key)
    {
        return $this->storage->get($key);
    }

    protected function write($key, $config)
    {
        $this->storage->set($key, $config);
    }


    private $sqlLikePattern;
    private $storage;
    private $bkpMarker = '__bkp_';

    private function findConfigKeys()
    {
        $configKeys = $this->storage->findKeysLike($this->sqlLikePattern);

        foreach ($configKeys as $i => $key) {
            if (strpos($key, $this->bkpMarker) !== false) {
                unset($configKeys[$i]);
            }
        }

        return $configKeys;
    }
}