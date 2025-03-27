<?php

class Elm_RegexListParser {
	const WARNING_PREFIX = 'preg_match():';

	private $lastRegexErrorMessage = null;
	private $oldErrorHandler = null;

	/**
	 * @param string $text
	 * @return Elm_RegexValidationResult
	 */
	public function parse($text) {
		$validPatterns = array();
		$errors = array();
		$validationSubject = 'abc';

		//Split the input string into lines.
		$lines = preg_split('@\R@', $text, 1000, PREG_SPLIT_NO_EMPTY);

		//An invalid regex pattern will trigger a warning. We need to catch that warning
		//to get the actual error message. preg_last_error() apparently just returns
		//a generic PREG_INTERNAL_ERROR code.
		$this->oldErrorHandler = set_error_handler(array($this, 'onRegexError'), E_WARNING);

		foreach ($lines as $line) {
			$line = trim($line);

			//Skip empty lines and comments. Comments start with the #hash character.
			if ( ($line === '') || (substr($line, 0, 1) === '#') ) {
				continue;
			}

			//PHP doesn't provide a direct way to validate a regular expression,
			//so we just try to use the regex and check if there's an error.
			$fullPattern = Elm_RegexFilter::preparePattern($line);
			$this->lastRegexErrorMessage = null;
			if ( preg_match($fullPattern, $validationSubject) !== false ) {
				$validPatterns[] = $line;
			} else {
				if ( $this->lastRegexErrorMessage !== null ) {
					$message = $this->lastRegexErrorMessage;
				} else if ( function_exists('preg_last_error_msg') ) {
					$message = preg_last_error_msg();
				} else {
					$message = self::getPregErrorMessage(preg_last_error());
				}
				$errors[] = new Elm_RegexFilterValidationError($line, $message);
			}
		}

		restore_error_handler();
		$this->oldErrorHandler = null;

		return new Elm_RegexValidationResult($validPatterns, $errors);
	}

	private static function getPregErrorMessage($code) {
		static $knownErrors = array(
			PREG_NO_ERROR              => 'No error',
			PREG_INTERNAL_ERROR        => 'Internal PCRE error',
			PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
			PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
			PREG_BAD_UTF8_ERROR        => 'Malformed UTF-8 data',
			PREG_BAD_UTF8_OFFSET_ERROR => 'Offset does not correspond to valid UTF-8 data',
		);

		//This constant was added in PHP 7.0, so it might not be defined.
		if ( defined('PREG_JIT_STACKLIMIT_ERROR') && ($code === PREG_JIT_STACKLIMIT_ERROR) ) {
			return 'Not enough JIT stack space';
		}

		if ( isset($knownErrors[$code]) ) {
			return $knownErrors[$code];
		}
		return sprintf('Unknown error code %d', $code);
	}

	public function onRegexError($level = 0, $message = '', $fileName = '', $line = 0, $deprecated = array()) {
		//Brief testing with PHP 5.2 to 8.1 suggests that warnings related to preg_match
		//always start with "preg_match():". We can skip other warnings.
		$message = ltrim($message);
		$prefixPosition = strpos($message, self::WARNING_PREFIX);
		if ( $prefixPosition !== 0 ) {
			if ( $this->oldErrorHandler !== null ) {
				return call_user_func($this->oldErrorHandler, $level, $message, $fileName, $line, $deprecated);
			}
			return false;
		}

		//Remove the common prefix. It's not relevant to the user.
		$message = trim($this->removePrefix($message, self::WARNING_PREFIX));

		//Also remove the "compilation failed" prefix. It looks like all warnings that are
		//related to regex syntax errors have this prefix.
		$message = trim($this->removePrefix($message, 'Compilation failed:'));

		$this->lastRegexErrorMessage = $message;
		return true;
	}

	private function removePrefix($inputString, $prefix) {
		$length = strlen($prefix);
		if ( substr($inputString, 0, $length) === $prefix ) {
			return substr($inputString, $length);
		}
		return $inputString;
	}
}