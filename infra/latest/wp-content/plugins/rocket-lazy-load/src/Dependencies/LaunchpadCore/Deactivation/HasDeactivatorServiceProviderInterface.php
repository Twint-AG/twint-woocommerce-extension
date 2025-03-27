<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Deactivation;

interface HasDeactivatorServiceProviderInterface extends DeactivationServiceProviderInterface {

	/**
	 * Returns list of deactivators.
	 *
	 * @return string[]
	 */
	public function get_deactivators(): array;
}
