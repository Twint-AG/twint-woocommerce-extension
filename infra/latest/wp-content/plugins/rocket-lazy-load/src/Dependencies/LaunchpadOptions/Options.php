<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\OptionsInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Traits\PrefixedKeyTrait;

/**
 * Manages single site options using the WordPress options API.
 */
class Options implements OptionsInterface {

    use PrefixedKeyTrait;

    /**
     * Constructor
     *
     * @param string $prefix options prefix.
     */
    public function __construct( string $prefix = '' ) {
        $this->prefix = $prefix;
    }

    /**
     * Gets the option for the given name. Returns the default value if the value does not exist.
     *
     * @param string $name   Name of the option to get.
     * @param mixed  $default Default value to return if the value does not exist.
     *
     * @return mixed
     */
    public function get( string $name, $default = null ) {
        $option = get_option( $this->get_full_key( $name ), $default );

        if ( is_array( $default ) && ! is_array( $option ) ) {
            $option = (array) $option;
        }

        return $option;
    }

    /**
     * Sets the value of an option. Update the value if the option for the given name already exists.
     *
     * @param string $name Name of the option to set.
     * @param mixed  $value Value to set for the option.
     *
     * @return void
     */
    public function set( string $name, $value ) {
        update_option( $this->get_full_key( $name ), $value );
    }

    /**
     * Deletes the option with the given name.
     *
     * @param string $name Name of the option to delete.
     *
     * @return void
     */
    public function delete( string $name ) {
        delete_option( $this->get_full_key( $name ) );
    }
}