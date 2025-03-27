<?php

class Elm_IgnoredMessageFilter extends Elm_LogFilter {
	/**
	 * @var array<string, bool>
	 */
	private $ignoredMessageIndex;
	/**
	 * @var array<string, array>
	 */
	private $fixedMessages;

	/**
	 * @var callable|null
	 */
	private $reportUnfixed;

	/**
	 * Elm_IgnoredMessageFilter constructor.
	 *
	 * @param Iterator $iterator
	 * @param array $ignoredMessages
	 * @param array $fixedMessages
	 * @param callable $reportUnfixed
	 */
	public function __construct(Iterator $iterator, $ignoredMessages, $fixedMessages = array(), $reportUnfixed = null) {
		parent::__construct($iterator);
		$this->ignoredMessageIndex = $ignoredMessages;
		$this->fixedMessages = $fixedMessages;
		$this->reportUnfixed = $reportUnfixed;
	}

	/**
	 * Check whether the current element of the iterator is acceptable
	 *
	 * @return bool true if the current element is acceptable, otherwise false.
	 */
	#[\ReturnTypeWillChange]
	public function accept() {
		$entry = $this->getInnerIterator()->current();
		if ( !isset($entry, $entry['message']) ) {
			return true;
		}

		if ( isset($this->fixedMessages[$entry['message']]) ) {
			//Let's check the timestamp.
			$details = $this->fixedMessages[$entry['message']];
			if ( empty($entry['timestamp']) || ($entry['timestamp'] <= $details['fixedOn']) ) {
				return false;
			} else {
				//This entry was logged after the error was marked as fixed, which means that
				//it hasn't actually been fixed.
				if ( $this->reportUnfixed ) {
					call_user_func($this->reportUnfixed, $entry['message']);
				}
				unset($this->fixedMessages[$entry['message']]);
				//The entry might still be hidden if it is ignored in addition to being
				//marked as fixed, so we continue on instead of returning from the method.
			}
		}

		if ( isset($this->ignoredMessageIndex[$entry['message']]) ) {
			$this->skippedEntryCount++;
			return false;
		} else {
			return true;
		}
	}
}