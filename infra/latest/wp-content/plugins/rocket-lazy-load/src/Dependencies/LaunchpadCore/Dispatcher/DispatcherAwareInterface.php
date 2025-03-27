<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Dispatcher;

use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Dispatcher;

interface DispatcherAwareInterface {

	/**
	 * Setup WordPress hooks dispatcher.
	 *
	 * @param Dispatcher $dispatcher WordPress hooks dispatcher.
	 *
	 * @return void
	 */
	public function set_dispatcher( Dispatcher $dispatcher ): void;
}
