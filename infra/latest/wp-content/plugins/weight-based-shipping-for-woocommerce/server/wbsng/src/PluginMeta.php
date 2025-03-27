<?php declare(strict_types=1);

namespace Gzp\WbsNg;

use GzpWbsNgVendors\Dgm\SimpleProperties\SimpleProperties;


/**
 * @property-read string|null $version
 * @property-read PluginPaths $paths
 */
class PluginMeta extends SimpleProperties
{
    public function __construct($entryFile)
    {
        $this->version = self::readVersionMeta($entryFile);
        $this->paths = new PluginPaths($entryFile);
    }


    protected $paths;
    protected $version;

    private static function readVersionMeta($entryFile): ?string
    {
        $pluginFileAttributes = get_file_data($entryFile, ['Version' => 'Version']);
        $version = $pluginFileAttributes['Version'] ?: null;
        return $version;
    }
}