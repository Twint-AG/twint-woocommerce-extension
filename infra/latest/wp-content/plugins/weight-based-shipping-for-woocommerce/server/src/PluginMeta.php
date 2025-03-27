<?php
namespace Wbs;

use WbsVendors\Dgm\SimpleProperties\SimpleProperties;


/**
 * @property-read string $version
 * @property-read PluginPaths $paths
 */
class PluginMeta extends SimpleProperties
{
    public function __construct($entryFile, $serverAppRoot)
    {
        $this->version = self::readVersionMeta($entryFile);
        $this->paths = new PluginPaths($serverAppRoot);
    }


    protected $paths;
    protected $version;

    static private function readVersionMeta($entryFile)
    {
        $pluginFileAttributes = get_file_data($entryFile, array('Version' => 'Version'));
        $version = $pluginFileAttributes['Version'] ?: null;
        return $version;
    }
}