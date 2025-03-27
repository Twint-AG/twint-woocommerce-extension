<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Traits;

trait PrefixedKeyTrait
{
    /**
     * The prefix used by options.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Gets the option name used to store the option in the WordPress database.
     *
     * @param string $name Unprefixed name of the option.
     *
     * @return string Option name used to store it
     */
    public function get_full_key( string $name ): string {
        return $this->prefix . $name;
    }

    /**
     * Checks if the option with the given name exists.
     *
     * @param string $name Name of the option to check.
     *
     * @return bool
     */
    public function has( string $name ): bool {
        return null !== $this->get( $name );
    }

    /**
     * Gets the option for the given name. Returns the default value if the value does not exist.
     *
     * @param string $name   Name of the option to get.
     * @param mixed  $default Default value to return if the value does not exist.
     *
     * @return mixed
     */
    abstract public function get( string $name, $default = null );
}