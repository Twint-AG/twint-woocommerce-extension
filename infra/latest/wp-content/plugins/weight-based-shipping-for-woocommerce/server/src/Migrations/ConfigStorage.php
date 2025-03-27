<?php
namespace Wbs\Migrations;

use WbsVendors\Dgm\Shengine\Migrations\AbstractConfigStorage;


class ConfigStorage extends AbstractConfigStorage
{
    public function forEachRule($fromConfig, $callback)
    {
        if (isset($fromConfig['rules']) && is_array($fromConfig['rules'])) {
            foreach ($fromConfig['rules'] as &$rule) {
                $rule = $callback($rule);
            }
        }

        return $fromConfig;
    }
}