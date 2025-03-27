<?php
class Elm_ExclusiveLock {
	protected $handle = null;
	protected $isAcquired = false;
	protected $fileName;

	public function __construct($name) {
		if ( !preg_match('@^[a-z0-9._+=\s\-]$@i', $name) ) {
			$name = 'elm-' . md5($name);
		}

		$directory = get_temp_dir();
		$this->fileName = $directory . $name . '.lock';
		$this->handle = @fopen($this->fileName, 'wb');
	}

	public function acquire() {
		//Don't try to take the same lock twice.
		if ( $this->isAcquired ) {
			return true;
		}
		if ( !$this->handle ) {
			return false;
		}

		if ( flock($this->handle, LOCK_EX) ) {
			$this->isAcquired = true;
			fwrite($this->handle, 'Locked on ' . date('c')); //For debugging.
		} else {
			trigger_error(
				sprintf('%s::%s failed to acquire a lock on file "%s"', __CLASS__, __METHOD__, $this->fileName),
				E_USER_WARNING
			);
		}
		return $this->isAcquired;
	}

	public function release() {
		if ( $this->handle && $this->isAcquired ) {
			flock($this->handle, LOCK_UN);
			$this->isAcquired = false;
		}
	}

	public function __destruct() {
		if ( $this->isAcquired ) {
			$this->release();
		}
		if ( $this->handle ) {
			fclose($this->handle);
			@unlink($this->fileName);
		}
	}
}