<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container;

use RocketLazyLoadPlugin\Dependencies\League\Container\Container;
use RocketLazyLoadPlugin\Dependencies\League\Container\Definition\DefinitionInterface;

class Registration {

	/**
	 * Container id.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Class definition.
	 *
	 * @var callable|null
	 */
	protected $definition;

	/**
	 * Container value.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Definition is shared.
	 *
	 * @var bool
	 */
	protected $shared = false;

	/**
	 * Instantiate the registration.
	 *
	 * @param string $id Id from the container.
	 */
	public function __construct( string $id ) {
		$this->id    = $id;
		$this->value = $id;
	}

	/**
	 * Define a callback definition for the class.
	 *
	 * @param callable $definition Callback definition for the class.
	 * @return $this
	 */
	public function set_definition( callable $definition ): Registration {
		$this->definition = $definition;
		return $this;
	}

	/**
	 * Set a concrete class.
	 *
	 * @param mixed $concrete Concrete class.
	 * @return $this
	 */
	public function set_concrete( $concrete ): Registration {
		$this->value = $concrete;
		return $this;
	}

	/**
	 * Make a definition shared.
	 *
	 * @return $this
	 */
	public function share(): Registration {
		$this->shared = true;
		return $this;
	}

	/**
	 * Register a definition on a container.
	 *
	 * @param Container $container Container to register on.
	 * @return void
	 */
	public function register( Container $container ) {
		$class_registration = $container->add( $this->id, $this->value, $this->shared );

		if ( ! $this->definition ) {
			return;
		}

		( $this->definition )( $class_registration );
	}
}
