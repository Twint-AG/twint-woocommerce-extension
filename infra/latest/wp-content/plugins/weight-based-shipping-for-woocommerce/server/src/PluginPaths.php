<?php

namespace Wbs;

use WbsVendors\Dgm\SimpleProperties\SimpleProperties;


/**
 * @property-read string $root
 * @property-read string $assets
 * @property-read string $tplFile
 * @property-read string $globalStubTplFile
 */
class PluginPaths extends SimpleProperties
{
    public function __construct($root)
    {
        $this->root = rtrim($root, '/\\');
        $this->assets = defined('WBS_DEV') ? "{$this->root}/../client/build" : "{$this->root}/..";
        $this->tplFile = "{$this->root}/tpl/main.php";
        $this->globalStubTplFile = "{$this->root}/tpl/global-stub.php";
    }

    public function getAssetUrl($asset = null): string
    {
        return plugins_url($asset, $this->assets.'/.');
    }

    protected $root;
    protected $assets;
    protected $tplFile;
    protected $globalStubTplFile;
}