<?php

/**
 * This class iterates over the lines in a file in reverse order (i.e. starting from the last line).
 *
 * Empty lines are included. Supports LF, CR and CRLF line endings.
 */
class Elm_ReverseLineIterator implements Iterator {
	/**
	 * @var resource
	 */
	private $filePointer;

	/**
	 * @var int|null How many lines to read from the log (max).
	 */
	private $maxLinesToRead = null;

	/**
	 * @var int|null Set this to start reading the file at a specific offset instead of the end of file.
	 */
	private $startPosition = null;

	private $endPosition = 0;

	private $currentLine = null;
	private $currentLineNumber = 0;
	private $currentLinePosition = 0;

	/**
	 * @var array[]
	 */
	private $lineBuffer = array();

	private $bufferIndex = -1;

	/**
	 * @var int Current seek position.
	 */
	private $position = 0;

	/**
	 * @var int Read buffer size.
	 */
	private $bufferSizeInBytes = 20480;

	/**
	 * @var string Buffer data left over from the previous readNextLine() iteration.
	 */
	private $remainder = '';

	/**
	 * @var int Lines longer than this will be truncated.
	 */
	private $lineLengthLimit = 60 * 1024;

	/**
	 * @var int How many bytes to scan for line breaks before giving up.
	 */
	private $lineBreakSearchLimit = 200 * 1024;

	public function __construct($fileName, $maxLines = null, $startPosition = null, $endPosition = 0) {
		$this->maxLinesToRead = $maxLines;

		//We read the file backwards, so the end offset must be *smaller* than the start offset.
		if ( isset($startPosition, $endPosition) && ($endPosition > $startPosition) ) {
			//Swap the two positions.
			$temp = $endPosition;
			$endPosition = $startPosition;
			$startPosition = $temp;
		}

		$this->startPosition = $startPosition;
		$this->endPosition = $endPosition;

		$this->filePointer = fopen($fileName, 'rb');
		if ( $this->filePointer === false ) {
			throw new RuntimeException(
				sprintf(
					__('Could not open the log file "%s".', 'error-log-monitor'),
					esc_html($fileName)
				)
			);
		}

		if ( $this->lineBreakSearchLimit < $this->lineLengthLimit ) {
			$this->lineBreakSearchLimit = $this->lineLengthLimit;
		}
	}

	public function __destruct() {
		if ( $this->filePointer ) {
			fclose($this->filePointer);
			$this->filePointer = null;
		}
	}

	#[\ReturnTypeWillChange]
	public function rewind() {
		//Start reading from the end of the file or from the specified position. Then move
		//back towards the start of the file, reading it in $bufferSizeInBytes blocks.
		if ( isset($this->startPosition) ) {
			fseek($this->filePointer, $this->startPosition, SEEK_SET);
		} else {
			fseek($this->filePointer, 0, SEEK_END);
			$this->startPosition = ftell($this->filePointer);
		}
		$this->position = ftell($this->filePointer);

		$this->lineBuffer = array();
		$this->bufferIndex = -1;
		$this->currentLine = null;
		$this->currentLineNumber = 0;
		$this->currentLinePosition = $this->position;

		$this->readNextLine();
	}

	private function readNextLine() {
		//Stop after $maxLinesToRead. Note that $this->currentLineNumber is zero-based.
		if ( isset($this->maxLinesToRead) && ($this->currentLineNumber >= $this->maxLinesToRead - 1) ) {
			$this->currentLine = null;
			return;
		}

		//Populate the internal buffer.
		while (($this->bufferIndex < 0) && ($this->position > $this->endPosition)) {
			//Since $position is an offset from the start of the file,
			//it's usually equal to the total amount of remaining data.
			$remainingBytes = $this->position - $this->endPosition;
			$bytesToRead = ($remainingBytes > $this->bufferSizeInBytes) ? $this->bufferSizeInBytes : $remainingBytes;

			$this->position = $this->position - $bytesToRead;
			fseek($this->filePointer, $this->position, SEEK_SET);
			$buffer = fread($this->filePointer, $bytesToRead);

			//We may have a partial line left over from the previous iteration.
			$buffer .= $this->remainder;

			$newLines = preg_split('@\n|\r\n?@', $buffer, -1, PREG_SPLIT_OFFSET_CAPTURE);

			//If we can't find a line break within N bytes, give up and return the whole buffer
			//as a line. Note that this means we may return a partial line.
			if ( (count($newLines) < 2) && (strlen($buffer) >= $this->lineBreakSearchLimit) ) {
				$this->remainder = '';
			} else {
				//It's likely that we'll start reading in the middle of a line (unless we're at
				//the beginning of the file), so lets leave the first line for later.
				if ( $this->position != $this->endPosition ) {
					$firstLine = array_shift($newLines);
					$this->remainder = $firstLine[0];
				}
			}

			$this->lineBuffer = $newLines;
			$this->bufferIndex = count($this->lineBuffer) - 1;
		}

		//Get the next line from the buffer.
		if ( $this->bufferIndex >= 0 ) {
			$this->currentLine = $this->lineBuffer[$this->bufferIndex][0];
			$this->currentLinePosition = $this->position + $this->lineBuffer[$this->bufferIndex][1];
			$this->currentLineNumber++;
			$this->bufferIndex--;

			if ( strlen($this->currentLine) > $this->lineLengthLimit ) {
				$this->currentLine = substr($this->currentLine, 0, $this->lineLengthLimit);
			}
		} else {
			$this->currentLine = null;
		}
	}

	#[\ReturnTypeWillChange]
	public function valid() {
		return isset($this->currentLine) && ($this->position >= 0);
	}

	#[\ReturnTypeWillChange]
	public function current() {
		return $this->currentLine;
	}

	#[\ReturnTypeWillChange]
	public function key() {
		return $this->currentLineNumber;
	}

	#[\ReturnTypeWillChange]
	public function next() {
		$this->readNextLine();
	}

	public function getStartPosition() {
		return $this->startPosition;
	}

	public function getPositionInFile() {
		return $this->currentLinePosition;
	}
}