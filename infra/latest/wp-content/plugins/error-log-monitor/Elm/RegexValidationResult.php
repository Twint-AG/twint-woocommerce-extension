<?php

class Elm_RegexValidationResult {
	private $validPatterns;
	private $errors;

	/**
	 * @param string[] $validPatterns
	 * @param Elm_RegexFilterValidationError[] $errors
	 */
	public function __construct($validPatterns = array(), $errors = array()) {
		$this->validPatterns = $validPatterns;
		$this->errors = $errors;
	}

	/**
	 * @return string[]
	 */
	public function getValidPatterns() {
		return $this->validPatterns;
	}

	/**
	 * @return Elm_RegexFilterValidationError[]
	 */
	public function getErrors() {
		return $this->errors;
	}
}