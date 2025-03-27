<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container;

interface HasInflectorInterface {

	/**
	 * Returns inflectors mapping.
	 *
	 * @return array<string,array>
	 */
	public function get_inflectors(): array;

	/**
	 * Register inflectors.
	 *
	 * @return void
	 */
	public function register_inflectors(): void;
}
