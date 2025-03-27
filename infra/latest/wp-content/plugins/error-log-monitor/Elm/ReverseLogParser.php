<?php
class Elm_ReverseLogParser implements OuterIterator {
	/**
	 * @var array Recognized error levels. See PHP source code: /main/main.c, function php_error_cb.
	 * The "unknown error" case was intentionally omitted.
	 */
	private static $builtinSeverityLevels = array(
		'fatal error' => true,
		'catchable fatal error' => true,
		'recoverable fatal error' => true,
		'parse error' => true,
		'warning' => true,
		'notice' => true,
		'strict standards' => true,
		'deprecated' => true,
	);

	/**
	 * @var Elm_ReverseLineIterator
	 */
	private $lineIterator;
	private $currentEntry = null;
	private $currentKey = 0;
	private $currentFileOffset = 0;

	/**
	 * @var array A circular buffer used to implement backtracking.
	 */
	private $backtrackBuffer = array();
	private $backtrackingBufferSize = 200;
	private $fileOffsetBuffer = array();

	/**
	 * @var int Next read index. Must not exceed the write index.
	 */
	private $bufferReadIndex = 0;

	/**
	 * @var int Next write index.
	 */
	private $bufferWriteIndex = 0;

	private $backtrackingIndexStack = array();

	/**
	 * @var bool Attempt to parse XDebug stack traces.
	 */
	private $isXdebugTraceEnabled = false;

	/**
	 * @var bool Attempt to parse stack traces that PHP 7 generates for fatal errors.
	 */
	private $isPhpDefaultTraceEnabled = false;

	/**
	 * @var int The maximum number of lines in a multi-line log entry. This doesn't include stack traces.
	 */
	private $maxMessageLines = 32;

	/**
	 * @var int The maximum length of a log entry (not incl. timestamp and stack). Longer entries will be truncated.
	 */
	private $maxMessageLength = 60 * 1024;

	public function __construct(Elm_ReverseLineIterator $lineIterator) {
		$this->lineIterator = $lineIterator;
		$this->isXdebugTraceEnabled = function_exists('extension_loaded') && extension_loaded('xdebug');
		$this->isPhpDefaultTraceEnabled = version_compare(phpversion(), '5.4', '>=');
		$this->backtrackingBufferSize = max(
			min(
				intval(ini_get('xdebug.max_nesting_level')) + 10,
				$this->backtrackingBufferSize
			),
			1000
		);
	}

	/**
	 * Read the next entry from the log and store it in $currentEntry.
	 */
	private function readNextEntry() {
		$this->currentEntry = null;
		if ( !$this->lineIterator->valid() && empty($this->backtrackBuffer) ) {
			return;
		}

		$this->currentKey++;

		//Read post-entry context.
		$context = $this->tryReadContext('previous');

		//Try to read a log entry with an XDebug stack trace.
		if ( $this->isXdebugTraceEnabled ) {
			$this->saveState();
			$this->currentEntry = $this->parseEntryWithXdebugTrace();
			if ( $this->currentEntry !== null ) {
				$this->complete();
			} else {
				$this->backtrack();
			}
		}

		//Try to read an entry with PHP7-style stack trace.
		if ( !isset($this->currentEntry) && $this->isPhpDefaultTraceEnabled ) {
			$this->saveState();
			$this->currentEntry = $this->parseEntryWithStackTrace();
			if ( $this->currentEntry !== null ) {
				$this->complete();
			} else {
				$this->backtrack();
			}
		}

		//Try to read a normal log entry.
		if ( !isset($this->currentEntry) ) {
			$this->currentEntry = $this->readMultiLineMessage();
		}

		if ( isset($this->currentEntry) && empty($this->currentEntry['isContext']) ) {
			//Read pre-entry context.
			if ( $context === null ) {
				$context = $this->tryReadContext();
			}

			if ( $context !== null ) {
				//TODO: Verify that the context data is about this particular entry.
				//Sometimes multi-line messages can be interleaved (probably due to concurrency).
				$this->currentEntry['context'] = $context;
			}
		}
	}

	private function parseEntryWithXdebugTrace() {
		/*
		 * The XDebug stack trace line format is hard to parse. It might even be impossible
		 * to do it unambiguously. The function call comes first and can contain virtually
		 * any characters. The file name is separated from it by a single space, and both
		 * the file name and the function call can contain spaces.
		 *
		 * Due to that, this method doesn't attempt to parse the individual lines aside from
		 * verifying the index.
		 */

		//Note: XDebug stack traces are deepest-call-last (i.e. most recent call last).
		$stackTraceRegex = '/^PHP[ ]{1,5}?(\d{1,3}?)\.\s./';
		$stackTrace = null;

		$line = $this->readParsedLine();
		if ( isset($line) && preg_match($stackTraceRegex, $line['message'], $matches) ) {
			$stackTrace = array($line['message']);
			$remainingTraceLines = intval($matches[1]) - 1;
		} else {
			return null;
		}

		for ( $traceIndex = $remainingTraceLines; $traceIndex > 0; $traceIndex-- ) {
			$line = $this->readParsedLine();
			if ( isset($line) && preg_match($stackTraceRegex, $line['message'], $matches) && (intval($matches[1]) === $traceIndex) ) {
				$stackTrace[] = $line['message'];
			} else {
				return null;
			}
		}

		$line = $this->readParsedLine();
		if ( isset($line) && ($line['message'] == 'PHP Stack trace:' ) ) {
			$stackTrace[] = $line['message'];
		} else {
			return null;
		}

		$entry = $this->readMultiLineMessage();
		if ( $entry === null ) {
			return null;
		}

		$entry['stacktrace'] = array_reverse($stackTrace);
		return $entry;
	}

	private function parseEntryWithStackTrace() {
		//Note: Native PHP stack traces are deepest-call-first (i.e. most recent call first).
		$stackTrace = array();

		//The last line of the stack trace can be "#123 /path/to/x.php..." or "  [thrown] in /path/to/x.php..."
		//However, the last line can be truncated at any point if the message + stack trace exceeds
		//the log_errors_max_len setting. log_errors_max_len defaults to 1024 bytes.
		$line = $this->readNextLine();
		if ( isset($line) && preg_match('/^(\s\sthrown in |#\d{1,3}\s\S)/', $line) ) {
			$item = $this->parsePhpStackTraceItem($line, true);
			if ( $item !== null ) {
				$stackTrace[] = $item;
			} else {
				$stackTrace[] = $line;
			}
		} else {
			return null;
		}

		//Read until we find a line with a timestamp. That's the first line.
		$traceLimit = 50;
		do {
			$entry = $this->readParsedLine();
			if ( !isset($entry) ) {
				return null;
			}

			//Potential bug: If the error message itself has multiple lines, all except the first line
			//will be treated as if they were part of the stack trace.
			if ( empty($entry['timestamp']) ) {
				$item = $this->parsePhpStackTraceItem($entry['message']);
				if ( $item !== null ) {
					$stackTrace[] = $item;
				} else {
					$stackTrace[] = $entry['message'];
				}
			} else {
				$stackTrace = array_reverse($stackTrace);
				//The stack trace always starts with "Stack trace:" on its own line.
				if ( $stackTrace[0] === 'Stack trace:' ) {
					$entry['stacktrace'] = $stackTrace;
					return $entry;
				} else {
					return null;
				}
			}
		} while ( count($stackTrace) < $traceLimit );

		return null;
	}

	private function parsePhpStackTraceItem($message, $isLastLine = false) {
		//It's usually "#123 C:\path\to\plugin.php(456): functionCallHere()".
		//The last line of a very long entry can be truncated.
		if ( preg_match(
			'@^\#(?P<index>\d++)\s  # Stack frame index.
			(?:
			    (?P<source>
			        \[internal\sfunction\]
			        | 
			        (?P<file>
			             (?:phar://)?          # PHAR archive prefix (optional).
			             (?:[a-zA-Z]:)?        # Drive letter (optional).
			             [^:?*<>{}]+           # File path.
			        ) \((?P<line>\d{1,6})\)    # Line number.
			    ):
			    | (?P<main>{main})\s*?$
			)@x',
			$message,
			$matches
		) ) {
			$item = array();

			if ( !empty($matches['source']) && !empty($matches[0]) ) {
				$item['call'] = ltrim(substr($message, strlen($matches[0])));
			} else if ( !empty($matches['main']) ) {
				$item['call'] = $matches['main'];
			}

			if ( !empty($matches['file']) ) {
				$item['file'] = $matches['file'];
			} else if ( !empty($matches['source']) ) {
				$item['file'] = $matches['source'];
			}

			if ( !empty($matches['line']) ) {
				$item['line'] = $matches['line'];
			}

			return $item;
		} else if (
			//Simplified parsing for truncated stack trace entries.
			$isLastLine
			&& preg_match('@^\#(?P<index>\d++)\s@', $message, $matches)
			&& preg_match('@\son\sline\s\d++$@', $message)
		) {
			$item = array('call' => trim(substr($message, strlen($matches[0]))));
			return $item;
		} else {
			return null;
		}
	}

	/**
	 * @param string $expectedParentPosition
	 * @return array|null
	 */
	private function tryReadContext($expectedParentPosition = 'next') {
		//If position is "previous", read all context lines and discard everything except the last one.
		//If it's "next", read just one.

		$context = null;
		do {
			$this->saveState();

			$line = $this->readParsedLine();
			if ( ($line === null) || !isset($line['contextPayload']) || !is_array($line['contextPayload']) ) {
				//This is not a context entry.
				$this->backtrack();
				break;
			}
			$this->complete();
			$context = $line['contextPayload'];

		} while (($line !== null) && ($expectedParentPosition === 'previous'));

		if ( $context !== null ) {
			$parentEntryPosition = isset($context['parentEntryPosition']) ? $context['parentEntryPosition'] : 'next';
			if ($parentEntryPosition === $expectedParentPosition) {
				return $context;
			}
		}
		return null;
	}

	/**
	 * Read a message that spans multiple lines.
	 * This is basically the same as readParsedLine() except it can handle messages that contain line breaks.
	 *
	 * @return array|null
	 */
	private function readMultiLineMessage() {
		//Optimization shortcut.
		$lastLine = $this->readParsedLine();
		if ( isset($lastLine, $lastLine['timestamp']) || ($lastLine === null) ) {
			return $lastLine;
		}

		$this->saveState();
		$messageLines = array($lastLine['message']);
		$totalLength = strlen($lastLine['message']);

		//The first line of a multi-line message is the only one that has a timestamp.
		do {
			$line = $this->readParsedLine();
			if ( $line === null ) {
				break;
			}

			if ( isset($line['timestamp']) ) {
				//This is the first line.
				if ( !empty($messageLines) ) {
					$line['message'] .= "\n" . implode("\n", array_reverse($messageLines));
				}
				if ( strlen($line['message']) > $this->maxMessageLength ) {
					$line['message'] = substr($line['message'], 0, $this->maxMessageLength);
				}
				$this->complete();
				return $line;
			} else {
				$messageLines[] = $line['message'];
				$totalLength += strlen($line['message']);
			}
		} while ( (count($messageLines) < $this->maxMessageLines) && ($totalLength < $this->maxMessageLength) );

		$this->backtrack();
		return $lastLine;
	}

	/**
	 * Save the current read state for later backtracking.
	 */
	private function saveState() {
		$this->backtrackingIndexStack[] = array($this->bufferReadIndex, $this->currentFileOffset);
	}

	/**
	 * Backtrack to the last saved state.
	 */
	private function backtrack() {
		if ( empty($this->backtrackingIndexStack) ) {
			throw new LogicException('Tried to backtrack but the stack is empty!');
		}
		list($this->bufferReadIndex, $this->currentFileOffset) = array_pop($this->backtrackingIndexStack);
	}

	/**
	 * Discard the last saved backtracking state. Call this when parsing succeeds.
	 */
	private function complete() {
		array_pop($this->backtrackingIndexStack);
	}

	/**
	 * Read a single line from the log, parsed into basic components (timestamp, the message itself, etc).
	 *
	 * @param bool $skipEmptyLines
	 * @return array|null
	 */
	private function readParsedLine($skipEmptyLines = true) {
		$line = $this->readNextLine($skipEmptyLines);
		if ( $line === null ) {
			return null;
		}
		return $this->parseLogLine($line);
	}

	private function parseLogLine($line) {
		$line = rtrim($line);
		$timestamp = null;
		$message = $line;
		$level = null;
		$context = null;

		/* TODO: Attempt to extract the file name and line number from the message.
		 *
		 * spprintf(&log_buffer, 0, "PHP %s:  %s in %s on line %" PRIu32, error_type_str, buffer, error_filename, error_lineno);
php_log_err_with_severity(log_buffer, syslog_type_int);

zend_error_va(severity, (file && ZSTR_LEN(file) > 0) ? ZSTR_VAL(file) : NULL, line,
"Uncaught %s\n  thrown", ZSTR_VAL(str));
		 */

		//We expect log entries to be structured like this: "[date-and-time] Optional severity: error message".
		$pattern = '/
			^(?:\[(?P<timestamp>[\w \-+:\/]{6,50}?)\]\ )?
			(?P<message>
			    (?:(?:PHP\ )?(?P<severity>[a-zA-Z][a-zA-Z ]{3,40}?):\ )?
			.+)$
		/x';

		if ( preg_match($pattern, $line, $matches) ) {
			$message = $matches['message'];

			if ( !empty($matches['timestamp']) ) {
				//Attempt to parse the timestamp, if any. Timestamp format can vary by server.
				$parsedTimestamp = strtotime($matches['timestamp']);
				if ( !empty($parsedTimestamp) ) {
					$timestamp = $parsedTimestamp;
				};
			}

			if ( !empty($matches['severity']) ) {
				//Parse the severity level.
				$levelName = strtolower(trim($matches['severity']));
				if ( isset(self::$builtinSeverityLevels[$levelName]) ) {
					$level = $levelName;
				}
			}

			//Does this line contain contextual data for another error?
			$contextPrefix = '[ELM_context_';
			$trimmedMessage = trim($message);
			if ( substr($trimmedMessage, 0, strlen($contextPrefix)) === $contextPrefix ) {
				$context = $this->parseContextLine($trimmedMessage);
			}
		}

		return array(
			'message' => $message,
			'timestamp' => $timestamp,
			'level' => $level,
			'isContext' => ($context !== null),
			'contextPayload' => $context,
		);
	}

	private function parseContextLine($message) {
		if ( !preg_match('@^\[(ELM_context_\d{1,8}?)\]@', $message, $matches) ) {
			return null;
		}

		$endTag = '[/' . $matches[1] . ']';
		$endTagPosition = strrpos($message, $endTag);
		if ( $endTagPosition === false ) {
			return null;
		}

		$serializedContext = substr(
			$message,
			strlen($matches[0]),
			$endTagPosition - strlen($matches[0])
		);
		$context = @json_decode($serializedContext, true);

		if ( !is_array($context) ) {
			return null;
		}

		if ( !isset($context['parentEntryPosition']) ) {
			$context['parentEntryPosition'] = 'next';
		}
		return $context;
	}

	/**
	 * Read a single line from the log.
	 *
	 * @param bool $skipEmptyLines
	 * @return string|null
	 */
	private function readNextLine($skipEmptyLines = true) {
		//Check the internal buffer first.
		while ( $this->bufferReadIndex < $this->bufferWriteIndex ) {
			$index = $this->bufferReadIndex % $this->backtrackingBufferSize;
			$line = $this->backtrackBuffer[$index];
			$offset = $this->fileOffsetBuffer[$index];

			$this->bufferReadIndex++;

			if ( !$skipEmptyLines || ($line !== '') ) {
				$this->currentFileOffset = $offset;
				return $line;
			}
		}

		$isBacktrackingBufferFull = !empty($this->backtrackingIndexStack)
			&& (($this->bufferWriteIndex - $this->backtrackingIndexStack[0][0]) === $this->backtrackingBufferSize);
		if ( $isBacktrackingBufferFull ) {
			//The current log entry is malformed or too large to fit in the buffer.
			return null;
		}

		//Then check the actual file iterator.
		while ( $this->lineIterator->valid() ) {
			$line = $this->lineIterator->current();
			$offset = $this->lineIterator->getPositionInFile();
			$this->lineIterator->next();

			if ( !empty($this->backtrackingIndexStack) ) {
				$index = $this->bufferWriteIndex % $this->backtrackingBufferSize;
				$this->backtrackBuffer[$index] = $line;
				$this->fileOffsetBuffer[$index] = $offset;

				$this->bufferWriteIndex++;
				$this->bufferReadIndex = $this->bufferWriteIndex;

				if ( $this->bufferWriteIndex - $this->backtrackingIndexStack[0][0] > $this->backtrackingBufferSize ) {
					//This should never happen in practice. Instead of overfilling the buffer,
					//the plugin should abort the current parse and fall back to something else.
					throw new LogicException('Backtrack buffer overflow');
				};
			}

			if ( !$skipEmptyLines || ($line !== '') ) {
				$this->currentFileOffset = $offset;
				return $line;
			}
		}

		$this->currentFileOffset = $this->lineIterator->getPositionInFile();
		return null;
	}

	/**
	 * Return the current log entry.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return $this->currentEntry;
	}

	/**
	 * Move forward to next log entry.
	 */
	#[\ReturnTypeWillChange]
	public function next() {
		$this->readNextEntry();
	}

	/**
	 * Return the key of the current entry.
	 * The key is not actually used by the plugin, but it is required by the Iterator interface.
	 *
	 * @return int|null
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return $this->currentKey;
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean
	 */
	#[\ReturnTypeWillChange]
	public function valid() {
		return isset($this->currentEntry);
	}

	/**
	 * Rewind the iterator to the last log entry.
	 */
	#[\ReturnTypeWillChange]
	public function rewind() {
		$this->lineIterator->rewind();
		$this->currentKey = 0;

		$this->backtrackingIndexStack = array();
		$this->backtrackBuffer = array();
		$this->bufferReadIndex = 0;
		$this->bufferWriteIndex = 0;

		$this->fileOffsetBuffer = array();
		$this->currentFileOffset = $this->lineIterator->getPositionInFile();

		$this->readNextEntry();
	}

	/**
	 * Returns the inner iterator.
	 *
	 * @return Elm_ReverseLineIterator
	 */
	#[\ReturnTypeWillChange]
	public function getInnerIterator() {
		return $this->lineIterator;
	}

	/**
	 * Returns the position of the current log entry in the log file, as an offset
	 * from the start of the file.
	 *
	 * @return int
	 */
	public function getPositionInFile() {
		return $this->currentFileOffset;
	}
}