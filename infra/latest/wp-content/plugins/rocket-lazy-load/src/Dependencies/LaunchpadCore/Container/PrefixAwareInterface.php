<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container;

interface PrefixAwareInterface {
	/**
	 * Set the plugin prefix.
	 *
	 * @param string $prefix Plugin prefix.
	 * @return void
	 */
	public function set_prefix( string $prefix ): void;
}
