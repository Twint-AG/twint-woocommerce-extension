<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting;

abstract class TabItem
{
    /**
     * Get the label of the tab
     */
    abstract public static function getLabel(): string;

    /**
     * Register the fields for setting tab.
     */
    abstract public static function fields(): array;

    /**
     * Get content to render to template HTML
     */
    abstract public static function getContents(array $data = []): string;

    abstract public static function allowSaveChanges(): bool;
}
