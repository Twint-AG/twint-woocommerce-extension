<?php

class Elm_RegexFilterValidationError implements Elm_ConfigurationError {
	/**
	 * @var string
	 */
	private $pattern;

	/**
	 * @var string|null
	 */
	private $pregErrorMessage;

	public function __construct($pattern, $pregErrorMessage = null) {
		$this->pattern = $pattern;
		$this->pregErrorMessage = $pregErrorMessage;
	}

	public function getHtml() {
		$output = sprintf(
			esc_html(__('Invalid regular expression in filter settings: %s', 'error-log-monitor')),
			sprintf('<br><code>%s</code>', esc_html($this->pattern))
		);

		if ( $this->pregErrorMessage ) {
			$output .= '<br>';
			$output .= esc_html(sprintf(
				_x('Error: %s', 'regex error code or message', 'error-log-monitor'),
				$this->pregErrorMessage
			));
		}

		return $output;
	}
}