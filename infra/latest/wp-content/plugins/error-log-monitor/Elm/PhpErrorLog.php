<?php

class Elm_PhpErrorLog {
	private $filename;

	public function __construct($filename) {
		$this->filename = $filename;
	}

	/**
	 * Get an instance of this class that represents the PHP error log.
	 * The log filename is detected automatically.
	 *
	 * @static
	 * @return Elm_PhpErrorLog|WP_Error An instance of this log reader, or WP_Error if error logging is not configured properly.
	 */
	public static function autodetect() {
		$logErrors = strtolower(strval(ini_get('log_errors')));
		$errorLoggingEnabled = !empty($logErrors) && !in_array($logErrors, array('off', '0', 'false', 'no'));
		$logFile = ini_get('error_log');

		//Check for common problems that could prevent us from displaying the error log.
		if ( !$errorLoggingEnabled ) {
			return new WP_Error(
				'log_errors_off',
				__('Error logging is disabled.', 'error-log-monitor')
			);
		} else if ( empty($logFile) ) {
			return new WP_Error(
				'error_log_not_set',
				__('Error log filename is not set.', 'error-log-monitor')
			);
		} else if ( (strpos($logFile, '/') === false) && (strpos($logFile, '\\') === false) ) {
			return new WP_Error(
				'error_log_uses_relative_path',
				sprintf(
					__('The current error_log value <code>%s</code> is not supported. Please change it to an absolute path.', 'error-log-monitor'),
					esc_html($logFile)
				)
			);
		} else if ( !is_readable($logFile) ) {
			if ( file_exists($logFile) ) {
				return new WP_Error(
					'error_log_not_accessible',
					sprintf (
						__('The log file <code>%s</code> exists, but is not accessible. Please check file permissions.', 'error-log-monitor'),
						esc_html($logFile)
					)
				);
			} else {
				return new WP_Error(
					'error_log_not_found',
					sprintf (
						__('The log file <code>%s</code> does not exist or is inaccessible.', 'error-log-monitor'),
						esc_html($logFile)
					)
				);
			}
		}

		return new self($logFile);
	}

	/**
	 * Get an iterator over log entries in reverse order (i.e. starting from the end of the file).
	 *
	 * @param int|null $maxLines If set, the iterator will stop after reading this many lines. NULL = no line limit.
	 * @param int|null $fromOffset Start reading from this byte offset. NULL = read from the end of the file.
	 * @param int $toOffset Stop reading at this byte offset. Default is 0, i.e. the beginning of the file.
	 * @return Elm_ReverseLogParser|WP_Error
	 */
	public function getIterator($maxLines = null, $fromOffset = null, $toOffset = 0) {
		try {
			$lineIterator = new Elm_ReverseLineIterator($this->getFilename(), $maxLines, $fromOffset, $toOffset);
		} catch (RuntimeException $exception) {
			return new WP_Error('error_log_fopen_failed', $exception->getMessage());
		}

		return new Elm_ReverseLogParser($lineIterator);
	}

	/**
	 * Clear the log.
	 * @return void
	 */
	public function clear() {
		$handle = fopen($this->filename, 'w');
		fclose($handle);
	}

	public function getFilename() {
		return $this->filename;
	}

	public function getFileSize() {
		return filesize($this->getFilename());
	}

	public function getModificationTime() {
		return filemtime($this->filename);
	}
}