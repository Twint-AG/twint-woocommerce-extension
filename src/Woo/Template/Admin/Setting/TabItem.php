<?php

namespace Twint\Woo\Template\Admin\Setting;

abstract class TabItem
{
    /**
     * Get the label of the tab
     *
     * @return string
     */
    abstract public static function getLabel(): string;

    /**
     * Register the fields for setting tab.
     * @return array
     */
    abstract public static function fields(): array;

    /**
     * Get content to render to template HTML
     *
     * @param array $data
     * @return string
     */
    abstract public static function getContents(array $data = []): string;

    /**
     * @return bool
     */
    abstract public static function allowSaveChanges(): bool;
}
