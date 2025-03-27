<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Traits\PrefixedKeyTrait;

class Transients implements Interfaces\TransientsInterface
{
    use PrefixedKeyTrait;

    /**
     * Constructor
     *
     * @param string $prefix transients prefix.
     */
    public function __construct( string $prefix = '' ) {
        $this->prefix = $prefix;
    }

    /**
     * Gets the transient for the given name. Returns the default value if the value does not exist.
     *
     * @param string $name   Name of the transient to get.
     * @param mixed  $default Default value to return if the value does not exist.
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $transient = get_transient( $this->get_full_key( $name ), $default );

        if ( is_array( $default ) && ! is_array( $transient ) ) {
            $option = (array) $transient;
        }

        return $transient;
    }

    /**
     * Sets the value of an transient. Update the value if the transient for the given name already exists.
     *
     * @param string $name Name of the transient to set.
     * @param mixed $value Value to set for the transient.
     * @param int $expiration Time until expiration in seconds. Default 0 (no expiration).
     *
     * @return void
     */
    public function set(string $name, $value, int $expiration = 0)
    {
        set_transient( $this->get_full_key( $name ), $value, $expiration );
    }

    /**
     * Deletes the transient with the given name.
     *
     * @param string $name Name of the transient to delete.
     *
     * @return void
     */
    public function delete(string $name)
    {
        delete_transient( $this->get_full_key( $name ) );
    }
}