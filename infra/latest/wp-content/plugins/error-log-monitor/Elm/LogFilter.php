<?php

abstract class Elm_LogFilter extends FilterIterator {
	protected $skippedEntryCount = 0;

	#[\ReturnTypeWillChange]
	public function rewind() {
		$this->skippedEntryCount = 0;
		parent::rewind();
	}

	/**
	 * Get the number of log entries that were skipped (i.e. filtered out) by this filter.
	 *
	 * @return int
	 */
	public function getSkippedEntryCount() {
		$count = $this->skippedEntryCount;

		$inner = $this->getInnerIterator();
		if ( $inner instanceof Elm_LogFilter ) {
			$count += $inner->getSkippedEntryCount();
		}

		return $count;
	}

	/**
	 * @return Elm_ReverseLogParser|null
	 */
	public function getLogParser() {
		$iterator = $this;
		do {
			$iterator = $iterator->getInnerIterator();
			if ( $iterator instanceof Elm_ReverseLogParser ) {
				return $iterator;
			}
		} while ($iterator !== null);

		return null;
	}
}