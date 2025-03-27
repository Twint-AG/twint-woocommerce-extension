<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions;

class Settings implements Interfaces\SettingsInterface
{

    /**
     * WordPress Option facade.
     *
     * @var Options
     */
    protected $options;

    /**
     * Settings option key.
     *
     * @var string
     */
    protected $settings_key;

    /**
     * Settings values.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Instantiate settings.
     *
     * @param Options $options WordPress Option facade.
     * @param string $settings_key Settings option key.
     */
    public function __construct(Options $options, string $settings_key = 'settings')
    {
        $this->options = $options;
        $this->settings_key = $settings_key;
        $this->settings = (array) $this->options->get($settings_key, []);
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null)
    {
        /**
         * Pre-filter any setting before read
         *
         * @param mixed $default The default value.
         */
        $value = apply_filters( "pre_get_{$this->settings_key}_" . $name, null, $default ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

        if ( null !== $value ) {
            return $value;
        }

        if( ! $this->has($name)) {
            return $default;
        }

        /**
         * Filter any setting after read
         *
         * @param mixed $default The default value.
         */
        return apply_filters( "get_{$this->settings_key}" . $name, $this->settings[$name], $default ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, $value)
    {
        $this->settings[$name] = $value;

        $this->persist();
    }

    /**
     * @inheritDoc
     */
    public function delete(string $name)
    {
        unset($this->settings[$name]);

        $this->persist();
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return key_exists($name, $this->settings);
    }

    /**
     * Persist the settings into the database.
     * @return void
     */
    protected function persist()
    {
        do_action("pre_persist_{$this->settings_key}", $this->settings);

        $this->options->set($this->settings_key, $this->settings);

        do_action("persist_{$this->settings_key}", $this->settings);
    }

    /**
     * Import multiple values at once.
     *
     * @param array<string,mixed> $values Values to import.
     *
     * @return void
     */
    public function import(array $values)
    {
        foreach ($values as $name => $value) {
            $this->settings[$name] = $value;
        }

        $this->persist();
    }

    /**
     * Export settings values.
     *
     * @return array<string,mixed>
     */
    public function dumps(): array
    {
        $output = [];

        foreach ($this->settings as $name => $value) {
            $output[$name] = $this->get($name);
        }

        return $output;
    }
}