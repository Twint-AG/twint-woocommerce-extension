<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container;

trait PrefixAware {

	/**
	 * Plugin prefix.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Set the plugin prefix.
	 *
	 * @param string $prefix Plugin prefix.
	 * @return void
	 */
	public function set_prefix( string $prefix ): void {
		$this->prefix = $prefix;
	}
}
