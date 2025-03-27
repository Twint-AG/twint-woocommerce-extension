<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\EventManagement\Wrapper;

use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\EventManagement\OptimizedSubscriberInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\EventManagement\SubscriberInterface;
use ReflectionClass;
use ReflectionException;

class SubscriberWrapper {


	/**
	 * Plugin prefix.
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * Instantiate class.
	 *
	 * @param string $prefix Plugin prefix.
	 */
	public function __construct( string $prefix ) {
		$this->prefix = $prefix;
	}

	/**
	 * Wrap a subscriber will the common interface for subscribers.
	 *
	 * @param object $instance Any class subscriber.
	 *
	 * @return SubscriberInterface
	 * @throws ReflectionException Error is the class name is not valid.
	 */
	public function wrap( $instance ): SubscriberInterface {
		if ( $instance instanceof OptimizedSubscriberInterface ) {
			return new WrappedSubscriber( $instance, $instance->get_subscribed_events() );
		}

		$methods          = get_class_methods( $instance );
		$reflection_class = new ReflectionClass( get_class( $instance ) );
		$events           = [];
		foreach ( $methods as $method ) {
			$method_reflection = $reflection_class->getMethod( $method );
			$doc_comment       = $method_reflection->getDocComment();
			if ( ! $doc_comment ) {
				continue;
			}
			$pattern = '#@hook\s(?<name>[a-zA-Z\\\-_$/]+)(\s(?<priority>[0-9]+))?#';

			preg_match_all( $pattern, $doc_comment, $matches, PREG_PATTERN_ORDER );
			if ( ! $matches ) {
				continue;
			}

			foreach ( $matches[0] as $index => $match ) {
				$hook = str_replace( '$prefix', $this->prefix, $matches['name'][ $index ] );

				$events[ $hook ][] = [
					$method,
					key_exists( 'priority', $matches ) && key_exists( $index, $matches['priority'] ) && '' !== $matches['priority'][ $index ] ? (int) $matches['priority'][ $index ] : 10,
					$method_reflection->getNumberOfParameters(),
				];
			}
		}

		return new WrappedSubscriber( $instance, $events );
	}
}
