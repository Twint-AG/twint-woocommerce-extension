<?php declare(strict_types=1);

namespace Gzp\WbsNg;


/**
 * @psalm-immutable
 */
class PluginPaths
{

    public function __construct($entryFile)
    {
        $this->entryFile = $entryFile;
        $root = dirname($this->entryFile);

        $this->serverTplDir = "$root/src";
        $this->serverPublicDirRelativeToRoot = "public";
    }

    public function serverAssetUrl(string $pathRelativeToPublicDir): string
    {
        return plugins_url("$this->serverPublicDirRelativeToRoot/$pathRelativeToPublicDir", $this->entryFile);
    }

    public function tpl(string $name): string
    {
        return "$this->serverTplDir/$name";
    }

    private $entryFile;
    private $serverTplDir;
    private $serverPublicDirRelativeToRoot;
}