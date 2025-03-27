<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Dispatcher\Sanitizer;

use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Interfaces\SanitizerInterface;

class SubscriberSignaturesSanitizer implements SanitizerInterface {

	/**
	 * Should return default value.
	 *
	 * @var bool
	 */
	protected $is_default = false;

	/**
	 * Sanitize the value.
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return array|false
	 */
	public function sanitize( $value ) {
		$this->is_default = false;

		if ( ! is_array( $value ) ) {
			$this->is_default = true;
			return false;
		}

		$output = [];

		foreach ( $value as $subscriber ) {
			if ( ! is_string( $subscriber ) && ! is_object( $subscriber ) ) {
				continue;
			}

			$output [] = $subscriber;
		}

		return $output;
	}

	/**
	 * Should return default value.
	 *
	 * @param mixed $value Current value.
	 * @param mixed $original Original value.
	 *
	 * @return bool
	 */
	public function is_default( $value, $original ): bool {
		return $this->is_default;
	}
}
