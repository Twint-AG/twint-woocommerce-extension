<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Deactivation;

use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container\AbstractServiceProvider;
use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container\HasInflectorInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container\PrefixAwareInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Dispatcher\DispatcherAwareInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Dispatcher;
use RocketLazyLoadPlugin\Dependencies\Psr\Container\ContainerInterface;

class Deactivation {

	/**
	 * Service providers.
	 *
	 * @var array
	 */
	protected static $providers = [];

	/**
	 * Parameters.
	 *
	 * @var array
	 */
	protected static $params = [];

	/**
	 * Container.
	 *
	 * @var ContainerInterface
	 */
	protected static $container;

	/**
	 * Hook dispatcher.
	 *
	 * @var Dispatcher
	 */
	protected static $dispatcher;

	/**
	 * Set service providers.
	 *
	 * @param array $providers Service providers.
	 * @return void
	 */
	public static function set_providers( array $providers ) {
		self::$providers = $providers;
	}

	/**
	 * Set parameters.
	 *
	 * @param array $params Parameters.
	 * @return void
	 */
	public static function set_params( array $params ) {
		self::$params = $params;
	}

	/**
	 * Set the container.
	 *
	 * @param ContainerInterface $container Container.
	 * @return void
	 */
	public static function set_container( ContainerInterface $container ) {
		self::$container = $container;
	}

	/**
	 * Set hook dispatcher.
	 *
	 * @param Dispatcher $dispatcher Hook dispatcher.
	 * @return void
	 */
	public static function set_dispatcher( Dispatcher $dispatcher ): void {
		self::$dispatcher = $dispatcher;
	}

	/**
	 * Performs these actions during the plugin deactivation
	 *
	 * @return void
	 */
	public static function deactivate_plugin() {

		$container = self::$container;

		foreach ( self::$params as $key => $value ) {
			$container->add( $key, $value );
		}

		$container->share( 'dispatcher', self::$dispatcher );

		$container->inflector( PrefixAwareInterface::class )->invokeMethod( 'set_prefix', [ key_exists( 'prefix', self::$params ) ? self::$params['prefix'] : '' ] );
		$container->inflector( DispatcherAwareInterface::class )->invokeMethod( 'set_dispatcher', [ $container->get( 'dispatcher' ) ] );

		$providers = array_filter(
			self::$providers,
			function ( $provider ) {
				if ( is_string( $provider ) ) {
					$provider = new $provider();
				}

				if ( ! $provider instanceof DeactivationServiceProviderInterface && ( ! $provider instanceof HasInflectorInterface || count( $provider->get_inflectors() ) === 0 ) ) {
					return false;
				}

				return $provider;
			}
			);

		$providers = array_map(
			function ( $provider ) {
				if ( is_string( $provider ) ) {
					return new $provider();
				}
				return $provider;
			},
			$providers
			);

		foreach ( $providers as $provider ) {
			$container->addServiceProvider( $provider );
		}

		foreach ( $providers as $service_provider ) {
			if ( ! $service_provider instanceof HasInflectorInterface ) {
				continue;
			}
			$service_provider->register_inflectors();
		}

		/**
		 * Deactivation providers.
		 *
		 * @param AbstractServiceProvider[] $providers Providers.
		 * @return AbstractServiceProvider[]
		 */
		$providers = apply_filters( "{$container->get('prefix')}deactivate_providers", $providers );

		foreach ( $providers as $provider ) {
			if ( ! $provider instanceof HasDeactivatorServiceProviderInterface ) {
				continue;
			}

			foreach ( $provider->get_deactivators() as $deactivator ) {
				$deactivator_instance = self::$container->get( $deactivator );
				if ( ! $deactivator_instance instanceof DeactivationInterface ) {
					continue;
				}
				$deactivator_instance->deactivate();
			}
		}
	}
}
