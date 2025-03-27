<?php

class Elm_RegexFilter extends Elm_LogFilter {
	const REGEX_DELIMITER = '~';

	/**
	 * @var string[]
	 */
	private $ignoredPatterns;

	/**
	 * @param Iterator $iterator
	 * @param string[] $ignoredPatterns
	 */
	public function __construct(Iterator $iterator, $ignoredPatterns) {
		parent::__construct($iterator);

		//The input patterns should not have any delimiters or modifiers. Let's add those now.
		$this->ignoredPatterns = array();
		foreach ($ignoredPatterns as $pattern) {
			$this->ignoredPatterns[] = self::preparePattern($pattern);
		}
	}

	#[\ReturnTypeWillChange]
	public function accept() {
		$entry = $this->getInnerIterator()->current();
		if ( !isset($entry, $entry['message']) ) {
			return true;
		}

		//Reject entries that match any of the specified regular expressions.
		$message = $entry['message'];
		foreach ($this->ignoredPatterns as $pattern) {
			if ( preg_match($pattern, $message) === 1 ) {
				$this->skippedEntryCount++;
				return false;
			}
		}
		return true;
	}

	/**
	 * Add delimiters and modifiers to a regular expression pattern.
	 *
	 * @param string $pattern
	 * @return string
	 */
	public static function preparePattern($pattern) {
		return self::REGEX_DELIMITER . $pattern . self::REGEX_DELIMITER . 'si';
	}
}