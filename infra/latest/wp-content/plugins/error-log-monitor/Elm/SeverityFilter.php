<?php

/**
 * Filters log entries by the severity level.
 */
class Elm_SeverityFilter extends Elm_LogFilter {
	const UNKNOWN_LEVEL_GROUP = 'other';

	private $isGroupIncluded = array();

	//Some severity levels have the same general meaning, so we map them to the same group.
	private static $severityMap = array(
		'fatal error' => 'error',
		'catchable fatal error' => 'error',
		'recoverable fatal error' => 'error',
		'parse error' => 'error',
	);

	public function __construct(Iterator $logIterator, $includedGroups = null) {
		parent::__construct($logIterator);

		//Include everything by default.
		$allGroups = self::getAvailableOptions();
		if ( !isset($includedGroups) ) {
			$includedGroups = $allGroups;
		}

		//Keep only supported levels.
		$includedGroups = array_intersect($includedGroups, $allGroups);

		$this->isGroupIncluded = array_merge(
			array_fill_keys($allGroups, false),
			array_fill_keys($includedGroups, true)
		);
	}

	public static function getAvailableOptions() {
		static $options = array(
			'error', 'warning', 'notice',
			'deprecated', 'strict standards',
			self::UNKNOWN_LEVEL_GROUP
		);
		return $options;
	}

	/**
	 * Read the last N entries from a PHP error log.
	 *
	 * @param int $count How many lines to read.
	 * @return array|WP_Error
	 */
	public function readLastEntries($count) {
		$filtered = array();

		foreach($this as $entry) {
			$filtered[] = $entry;

			if ( count($filtered) >= $count ) {
				break;
			}
		}

		return array_reverse($filtered);
	}

	private function isSeverityLevelIncluded($severityLevel) {
		if ( !isset($severityLevel) ) {
			$group = self::UNKNOWN_LEVEL_GROUP;
		} else if ( isset(self::$severityMap[$severityLevel]) ) {
			$group = self::$severityMap[$severityLevel];
		} else {
			$group = $severityLevel;
		}

		if ( !isset($this->isGroupIncluded[$group]) ) {
			$group = self::UNKNOWN_LEVEL_GROUP;
		}

		return $this->isGroupIncluded[$group];
	}

	/**
	 * Convert a list of severity groups to a list of PHP error severity levels.
	 * The "other/unknown" group is represented by NULL.
	 *
	 * @param string[] $severityGroups
	 * @return array
	 */
	public static function groupsToLevels($severityGroups) {
		if ( empty($severityGroups) ) {
			return array();
		}

		$invertedMap = array();
		foreach(self::$severityMap as $level => $group) {
			if ( !isset($invertedMap[$group]) ) {
				$invertedMap[$group] = array();
			}
			$invertedMap[$group][] = $level;
		}

		$levels = array();
		foreach($severityGroups as $group) {
			if ( $group === self::UNKNOWN_LEVEL_GROUP ) {
				$levels[] = null;
			} else if ( isset($invertedMap[$group]) ) {
				$levels = array_merge($levels, $invertedMap[$group]);
			} else {
				$levels[] = $group;
			}
		}
		return $levels;
	}

	public function formatSkippedEntryCount() {
		printf(
			_n(
				'%d entry was filtered out.',
				'%d entries were filtered out.',
				$this->getSkippedEntryCount(),
				'error-log-monitor'
			),
			$this->getSkippedEntryCount()
		);
	}

	/**
	 * Check whether the current element of the iterator is acceptable
	 *
	 * @link http://php.net/manual/en/filteriterator.accept.php
	 * @return bool true if the current element is acceptable, otherwise false.
	 * @since 5.1.0
	 */
	#[\ReturnTypeWillChange]
	public function accept() {
		$entry = $this->getInnerIterator()->current();
		if ( !isset($entry) ) {
			return true;
		}

		if ( $this->isSeverityLevelIncluded(isset($entry['level']) ? $entry['level'] : self::UNKNOWN_LEVEL_GROUP) ) {
			return true;
		} else {
			$this->skippedEntryCount++;
			return false;
		}
	}
}