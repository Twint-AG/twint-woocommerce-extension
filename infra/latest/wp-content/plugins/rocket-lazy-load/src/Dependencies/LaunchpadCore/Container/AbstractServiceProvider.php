<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container;

use RocketLazyLoadPlugin\Dependencies\League\Container\Container;
use RocketLazyLoadPlugin\Dependencies\League\Container\ContainerAwareInterface;
use RocketLazyLoadPlugin\Dependencies\League\Container\Definition\DefinitionInterface;
use RocketLazyLoadPlugin\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider as LeagueServiceProvider;

abstract class AbstractServiceProvider extends LeagueServiceProvider implements ServiceProviderInterface {

	/**
	 * Services to load.
	 *
	 * @var array
	 */
	protected $services_to_load = [];

	/**
	 * Indicates if the service provider already loaded.
	 *
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * Return IDs provided by the Service Provider.
	 *
	 * @return string[]
	 */
	public function declares(): array {
		return $this->provides;
	}

	/**
	 * Returns a boolean if checking whether this provider provides a specific
	 * service or returns an array of provided services if no argument passed.
	 *
	 * @param string $alias Class searched.
	 *
	 * @return boolean
	 */
	public function provides( string $alias ): bool {
		if ( ! $this->loaded ) {
			$this->loaded = true;
			$this->define();
		}

		return parent::provides( $alias );
	}

	/**
	 * Return IDs from front subscribers.
	 *
	 * @return string[]
	 */
	public function get_front_subscribers(): array {
		return [];
	}

	/**
	 * Return IDs from admin subscribers.
	 *
	 * @return string[]
	 */
	public function get_admin_subscribers(): array {
		return [];
	}

	/**
	 * Return IDs from common subscribers.
	 *
	 * @return string[]
	 */
	public function get_common_subscribers(): array {
		return [];
	}

	/**
	 * Return IDs from init subscribers.
	 *
	 * @return string[]
	 */
	public function get_init_subscribers(): array {
		return [];
	}

	/**
	 * Register service into the provider.
	 *
	 * @param string        $classname Class to register.
	 * @param callable|null $method Method called when registering.
	 * @param string        $concrete Concrete class when necessary.
	 * @return Registration
	 */
	public function register_service( string $classname, callable $method = null, string $concrete = '' ): Registration {

		$registration = new Registration( $classname );

        if( $method ) {
            $registration->set_definition( $method );
        }



		if ( $concrete ) {
			$registration->set_concrete( $concrete );
		}

		$this->services_to_load[] = $registration;

		if ( ! in_array( $classname, $this->provides, true ) ) {
			$this->provides[] = $classname;
		}

		return $registration;
	}

	/**
	 * Define classes.
	 *
	 * @return mixed
	 */
	abstract protected function define();

	/**
	 * Register classes provided by the service provider.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->services_to_load as $registration ) {
			$registration->register( $this->getLeagueContainer() );
		}
	}
}
