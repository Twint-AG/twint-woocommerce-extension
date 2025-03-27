<?php

class Elm_Plugin {
	const MB_IN_BYTES = 1048576; //= 1024 * 1024
	const MAX_NOTIFICATION_EMAIL_ADDRESSES = 30;

	/**
	 * @var scbOptions $settings Plugin settings.
	 */
	protected $settings;
	private $emailCronJob = null;
	private $pluginFile = '';

	private $wizard;

	/**
	 * @var scbCron
	 */
	private $sizeNotificationCronJob = null;

	private $fixedMessageCleanupCronJob;

	private $blacklistChangeBuffer = array();
	private $isBlacklistFlushCallbackSet = false;

	public function __construct($pluginFile) {
		$this->pluginFile = $pluginFile;

		$this->settings = new Elm_Settings('ws_error_log_monitor_settings', $pluginFile, $this->getDefaultSettings());

		if ( did_action('plugins_loaded') > 0 ) {
			$this->loadTextDomain();
		} else {
			add_action('plugins_loaded', array($this, 'loadTextDomain'));
		}

		add_action('init', array($this, 'initSetupWizard'));

		$this->createDashboardWidget();
		add_action('elm_settings_changed', array($this, 'onWidgetSettingsChanged'));

		$this->emailCronJob = new scbCron(
			$pluginFile,
			array(
				'interval' => min(
					$this->settings->get('email_interval'),
					$this->settings->get('email_log_check_interval')
				),
				'callback' => array($this, 'emailErrors'),
			)
		);

		$this->sizeNotificationCronJob = new scbCron(
			$pluginFile,
			array(
				'interval' => $this->settings->get('log_size_check_interval'),
				'callback' => array($this, 'checkLogFileSize'),
			)
		);

		$this->fixedMessageCleanupCronJob = new scbCron(
			$pluginFile,
			array(
				'interval' => defined('MONTH_IN_SECONDS') ? MONTH_IN_SECONDS : (30 * 24 * 3600),
				'callback' => array($this, 'deleteOldFixedMessages'),
			)
		);
	}

	protected function getDefaultSettings() {
		return array(
			'widget_line_count'        => 20,
			'strip_wordpress_path'     => false,
			'send_errors_to_email'     => array(),
			'email_line_count'         => 100,
			'email_interval'           => 3600, //seconds
			'email_log_check_interval' => 3600,

			'last_sent_email_timestamp'   => 0,
			//The end of the time interval that was covered by the last notification email.
			'last_email_log_interval_end' => null,
			'email_last_line_timestamp'   => 0, //Superseded by last_email_log_interval_end.

			'timestamp_format'        => 'M d, H:i:s',
			'sort_order'              => 'chronological',
			'extra_filter_line_count' => 1000,
			'dashboard_log_layout'    => 'list',

			'enable_log_size_notification'    => false,
			'log_size_notification_threshold' => self::MB_IN_BYTES, //bytes
			'log_size_check_interval'         => 3600, //seconds,
			'log_size_notification_sent'      => false,

			'dashboard_message_filter'        => 'all',
			'dashboard_message_filter_groups' => Elm_SeverityFilter::getAvailableOptions(),
			'email_message_filter'            => 'same_as_dashboard',
			'email_message_filter_groups'     => Elm_SeverityFilter::getAvailableOptions(),

			'ignored_messages' => array(),
			'fixed_messages'   => array(),

			'regex_filter_text'     => '',
			'regex_filter_patterns' => array(),

			'context_group_visibility' => array(
				'stacktrace' => true,
			),

			'enable_premium_notice' => true,
		);
	}

	public function loadTextDomain() {
		load_plugin_textdomain('error-log-monitor', false, basename(dirname($this->pluginFile)));
	}

	protected function createDashboardWidget() {
		Elm_DashboardWidget::getInstance($this->settings, $this);
	}

	/**
	 * @param scbOptions $newSettings
	 */
	public function onWidgetSettingsChanged($newSettings) {
		$this->updateEmailSchedule($newSettings);
		$this->installFixedMessageCleanupJob();
	}

	/**
	 * @param scbOptions $newSettings
	 */
	public function updateEmailSchedule($newSettings) {
		$emails = $newSettings->get('send_errors_to_email');
		if ( empty($emails) ) {
			$this->emailCronJob->unschedule();
		} else {
			$this->emailCronJob->reschedule(array(
				'interval' => min(
					$newSettings->get('email_interval'),
					$newSettings->get('email_log_check_interval')
				),
			));
		}

		if ( !$newSettings->get('enable_log_size_notification') ) {
			$this->sizeNotificationCronJob->unschedule();
		} else {
			$this->sizeNotificationCronJob->reschedule(array('interval' => $newSettings->get('log_size_check_interval')));
		}
	}

	public function emailErrors() {
		$emails = $this->settings->get('send_errors_to_email');
		if ( empty($emails) ) {
			//Can't send errors to email if no email address is specified.
			return;
		}

		$lock = new Elm_ExclusiveLock('elm-email-errors');
		$lock->acquire();
		//Note: Locking failures are intentionally ignored. Most of them are likely to be caused by file permissions,
		//which are either intentional (making "/wp-content/uploads" non-writable) or not easily fixed by the user.
		//It's better to occasionally send multiple email notifications than to never send any.

		//Make sure emails are sent no more than once every email_interval seconds.
		$timeSinceLastEmail = time() - $this->settings->get('last_sent_email_timestamp');
		$desiredInterval = $this->settings->get('email_interval');
		//Some deviation is acceptable since WP cron is not very precise.
		$acceptableDeviation = min(120, intval($desiredInterval * 0.1));
		if ( $timeSinceLastEmail < ($desiredInterval - $acceptableDeviation) ) {
			$lock->release();
			return;
		}

		$intervalStart = $this->settings->get(
			'last_email_log_interval_end',
			$this->settings->get('email_last_line_timestamp', 0)
		);

		$notification = $this->prepareEmailNotification($intervalStart);
		if ( empty($notification) ) {
			$lock->release();
			return;
		}

		$subject = sprintf(
			__('PHP errors logged on %s', 'error-log-monitor'),
			site_url()
		);
		$body = $notification['body'];

		if ( wp_mail($this->settings->get('send_errors_to_email'), $subject, $body) ) {
			$this->settings->set('last_email_log_interval_end', $notification['intervalEnd']);
			$this->settings->set('last_sent_email_timestamp', time());
		} else {
			trigger_error('Failed to send an email, wp_mail() returned FALSE', E_USER_WARNING);
		}

		$lock->release();
	}

	protected function prepareEmailNotification($intervalStart) {
		return $this->prepareNewEntriesNotification($intervalStart);
	}

	protected function prepareNewEntriesNotification($intervalStart) {
		$log = Elm_PhpErrorLog::autodetect();
		if ( is_wp_error($log) ) {
			trigger_error('Error log not detected', E_USER_WARNING);
			return null;
		}

		$filteredLog = $this->getEmailEntries($log);

		if ( is_wp_error($filteredLog) ) {
			trigger_error('Error log is not accessible', E_USER_WARNING);
			return null;
		}

		$lines = $filteredLog->readLastEntries($this->settings->get('email_line_count'));

		//Only include messages logged since the previous email.
		$logEntries = array();
		$foundNewMessages = false;
		$lastEntryTimestamp = time(); //Fall-back value in case none of the new entries have a timestamp.

		foreach ($lines as $line) {
			$foundNewMessages = $foundNewMessages || ($line['timestamp'] > $intervalStart);
			if ( $foundNewMessages ) {
				$logEntries[] = $line;
			}
			if ( !empty($line['timestamp']) ) {
				$lastEntryTimestamp = $line['timestamp'];
			}
		}

		if ( empty($logEntries) ) {
			return null;
		}

		$body = sprintf(
		/* translators: 1: Site URL, 2: Number of log entries, 3: Log file name */
			__("New PHP errors have been logged on %1\$s\nHere are the last %2\$d entries from %3\$s:\n\n", 'error-log-monitor'),
			site_url(),
			count($logEntries),
			$log->getFilename()
		);

		if ( $this->settings->get('sort_order') === 'reverse-chronological' ) {
			$logEntries = array_reverse($logEntries);
		}

		$stripWordPressPath = $this->settings->get('strip_wordpress_path');
		foreach ($logEntries as $logEntry) {
			if ( $stripWordPressPath ) {
				$logEntry['message'] = $this->stripWpPath($logEntry['message']);
			}
			if ( !empty($logEntry['timestamp']) ) {
				$body .= '[' . $this->formatTimestamp($logEntry['timestamp']) . '] ';
			}
			$body .= $logEntry['message'] . "\n";

			//Include the stack trace. Note how this doesn't count towards the line limit.
			if ( !empty($logEntry['stacktrace']) ) {
				foreach ($logEntry['stacktrace'] as $traceItem) {
					$body .= "\t" . self::getStackTraceItemText($traceItem) . "\n";
				}
			}
		}

		if ( $filteredLog->getSkippedEntryCount() > 0 ) {
			$body .= "\n\n" . $filteredLog->formatSkippedEntryCount() . "\n\n";
		}

		return array(
			'body'        => $body,
			'intervalEnd' => $lastEntryTimestamp,
		);
	}

	/**
	 * Get the plain text representation of a stack trace item.
	 *
	 * @param string|array $traceItem
	 * @return string
	 */
	public static function getStackTraceItemText($traceItem) {
		if ( is_string($traceItem) ) {
			return $traceItem;
		}

		$text = '';
		if ( isset($traceItem['call']) ) {
			$text .= $traceItem['call'];
		}

		if ( isset($traceItem['file']) ) {
			if ( isset($traceItem['call']) ) {
				$text .= ' ';
			}
			$text .= $traceItem['file'];
			if ( isset($traceItem['line']) ) {
				$text .= ':' . $traceItem['line'];
			}
		}

		return $text;
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 * @return Elm_SeverityFilter|WP_Error
	 */
	public function getWidgetEntries(Elm_PhpErrorLog $log) {
		return $this->getFilteredEntries(
			$log,
			$this->getIncludedGroupsForDashboard(),
			$this->settings->get('widget_line_count')
		);
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 * @return Elm_SeverityFilter|WP_Error
	 */
	private function getEmailEntries(Elm_PhpErrorLog $log) {
		return $this->getFilteredEntries(
			$log,
			$this->getIncludedGroupsForEmail(),
			$this->settings->get('email_line_count')
		);
	}

	/**
	 * Get a filtering iterator over log entries.
	 *
	 * @param Elm_PhpErrorLog $log
	 * @param array $includedGroups
	 * @param int $desiredEntryCount
	 * @return Elm_SeverityFilter|WP_Error
	 */
	protected function getFilteredEntries(Elm_PhpErrorLog $log, $includedGroups, $desiredEntryCount) {
		$maxLinesToRead = $desiredEntryCount;
		if ( $maxLinesToRead !== null ) {
			$maxLinesToRead = $desiredEntryCount + $this->settings->get('extra_filter_line_count');
		}

		$logIterator = $log->getIterator($maxLinesToRead);
		if ( is_wp_error($logIterator) ) {
			return $logIterator;
		}

		$currentFilter = new Elm_IgnoredMessageFilter(
			$logIterator,
			$this->settings->get('ignored_messages'),
			$this->settings->get('fixed_messages', array()),
			array($this, 'handleFixedErrorRecurrence')
		);

		$patterns = $this->settings->get('regex_filter_patterns', array());
		if ( !empty($patterns) && is_array($patterns) ) {
			$currentFilter = new Elm_RegexFilter($currentFilter, $patterns);
		}

		return new Elm_SeverityFilter($currentFilter, $includedGroups);
	}

	public function getIncludedGroupsForDashboard() {
		if ( $this->settings->get('dashboard_message_filter') === 'all' ) {
			return Elm_SeverityFilter::getAvailableOptions();
		} else {
			return $this->settings->get('dashboard_message_filter_groups');
		}
	}

	protected function getIncludedGroupsForEmail() {
		if ( $this->settings->get('email_message_filter') === 'same_as_dashboard' ) {
			return $this->getIncludedGroupsForDashboard();
		} else {
			return $this->settings->get('email_message_filter_groups');
		}
	}

	/**
	 * Handle a situation where an error that has been marked as fixed happens again.
	 *
	 * @param string $message Log entry message.
	 * @noinspection PhpUnused It is in fact used as a callback for Elm_IgnoredMessageFilter.
	 */
	public function handleFixedErrorRecurrence($message) {
		$this->queueBlacklistRemoval('fixed_messages', $message);
	}

	/**
	 * Add a message to one of the internal filter blacklists.
	 *
	 * @param string $list
	 * @param string $message
	 * @param bool $addToList
	 * @param mixed $value
	 * @param string $hookName
	 * @return array
	 */
	public function updateMessageBlacklist($list, $message, $addToList, $value, $hookName) {
		$this->applyBlacklistChanges(array(
			array(
				'list'      => $list,
				'message'   => $message,
				'addToList' => $addToList,
				'value'     => $value,
				'hookName'  => $hookName,
			),
		));
		return $this->settings->get($list, array());
	}

	/**
	 * Queue a message to be removed from one of the internal filter blacklists.
	 *
	 * Call {@see flushBlacklistChanges} to apply any queued changes.
	 * If the queue isn't explicitly flushed, it will happen on shutdown.
	 *
	 * @param string $listName
	 * @param string $message
	 * @param string|null $hookName
	 */
	public function queueBlacklistRemoval($listName, $message, $hookName = null) {
		if ( !$this->isBlacklistFlushCallbackSet ) {
			add_action('shutdown', array($this, 'flushBlacklistChanges'));
			$this->isBlacklistFlushCallbackSet = true;
		}

		if ( ($hookName === null) && ($listName === 'fixed_messages') ) {
			$hookName = 'elm_fixed_status_changed';
		}

		$this->blacklistChangeBuffer[] = array(
			'list'      => $listName,
			'message'   => $message,
			'addToList' => false,
			'value'     => null,
			'hookName'  => $hookName,
		);
	}

	/**
	 * @access protected
	 */
	public function flushBlacklistChanges() {
		if ( empty($this->blacklistChangeBuffer) ) {
			return;
		}
		$this->applyBlacklistChanges($this->blacklistChangeBuffer);

		//Clear the buffer.
		$this->blacklistChangeBuffer = array();
	}

	protected function applyBlacklistChanges($changes) {
		$lists = array();
		$hooksToCall = array();

		//Avoid race conditions.
		//Step 1: Use locks.
		$handle = fopen(__FILE__, 'r');
		flock($handle, LOCK_EX);
		//Step 2: Force WP to reload options from the database. Normally this would be
		//bad for performance, but it's acceptable in a rarely used AJAX handler.
		wp_cache_delete('alloptions', 'options');

		foreach ($changes as $change) {
			$listName = $change['list'];
			$message = $change['message'];
			$value = $change['value'];

			if ( !isset($lists[$listName]) ) {
				$lists[$listName] = $this->settings->get($listName, array());
			}

			if ( $change['addToList'] ) {
				//Add the message to the list.
				$lists[$listName][$message] = $value;
				$isOnTheList = true;
			} else {
				//Remove the message from the list.
				unset($lists[$listName][$message]);
				$isOnTheList = false;
			}

			if ( isset($change['hookName']) ) {
				$hooksToCall[] = array(
					$change['hookName'],
					array($message, $isOnTheList, ($isOnTheList ? $value : null)),
				);
			}
		}

		foreach ($lists as $listName => $newValues) {
			$this->settings->set($listName, $newValues);
		}

		flock($handle, LOCK_UN);

		foreach ($hooksToCall as $hook) {
			do_action_ref_array($hook[0], $hook[1]);
		}
	}

	public function updateLogSummary() {
		//Not implemented.
		return array();
	}

	public function stripWpPath($string) {
		return str_replace(rtrim(ABSPATH, '/\\'), '', $string);
	}

	public function formatTimestamp($timestamp) {
		return get_date_from_gmt(
			gmdate('Y-m-d H:i:s', $timestamp),
			$this->settings->get('timestamp_format')
		);
	}

	/**
	 * Format a log message for display. Does not escape special characters.
	 *
	 * @param string $message
	 * @param null $level
	 * @return string
	 */
	public function formatLogMessage($message, $level = null) {
		if ( $this->settings->get('strip_wordpress_path') ) {
			$message = $this->stripWpPath($message);
		}

		//Remove the "PHP" prefix from known severity levels.
		static $removablePrefix = 'PHP ';
		if ( $level !== null ) {
			$levelPrefix = $removablePrefix . $level;
			if ( strcasecmp(substr($message, 0, strlen($levelPrefix)), $levelPrefix) === 0 ) {
				$message = substr($message, strlen($removablePrefix));
			}
		}

		return $message;
	}

	public function checkLogFileSize() {
		if ( !$this->settings->get('enable_log_size_notification') ) {
			return;
		}

		$log = Elm_PhpErrorLog::autodetect();
		if ( is_wp_error($log) ) {
			return;
		}

		$lock = new Elm_ExclusiveLock('elm-email-errors');
		$lock->acquire();

		//Don't send multiple notifications about log size.
		if ( $this->settings->get('log_size_notification_sent') ) {
			return;
		}

		if ( $log->getFileSize() >= $this->settings->get('log_size_notification_threshold') ) {
			$subject = sprintf(
			/* translators: 1: File size limit, 2: Site URL */
				__('PHP error log file size has exceeded %1$s on %2$s', 'error-log-monitor'),
				self::formatByteCount($this->settings->get('log_size_notification_threshold'), 0),
				site_url()
			);
			$body = sprintf(
			/* translators: 1: Site URL, 2: Log file name, 3: Log file size */
				__("Site URL: %1\$s\nLog file: %2\$s\nSize: %3\$s\n", 'error-log-monitor'),
				site_url(),
				$log->getFilename(),
				self::formatByteCount($log->getFileSize())
			);

			if ( wp_mail($this->settings->get('send_errors_to_email'), $subject, $body) ) {
				$this->settings->set('log_size_notification_sent', true);
			} else {
				trigger_error('Failed to send an email, wp_mail() returned FALSE', E_USER_WARNING);
			}
		}

		$lock->release();
	}

	/**
	 * Delete old entries from the "marked as fixed" list.
	 *
	 * @noinspection PhpUnused This is actually used as a callback for a cron job.
	 */
	public function deleteOldFixedMessages() {
		static $reallyLongTime = 6 * MONTH_IN_SECONDS;

		$fixedMessages = $this->settings->get('fixed_messages', array());
		if ( empty($fixedMessages) ) {
			return; //Nothing to do.
		}

		$filteredList = array();
		foreach ($fixedMessages as $message => $details) {
			$delta = isset($details['fixedOn']) ? abs(time() - $details['fixedOn']) : 0;
			if ( $delta < $reallyLongTime ) {
				$filteredList[$message] = $details;
			}
		}

		$this->settings->set('fixed_messages', $filteredList);

		//Notice that we don't update the summary table here (for the Pro version).
		//That is not necessary because old summary entries are deleted automatically anyway.
	}

	/**
	 * Convert an amount of data in bytes to a more human-readable format like KiB or MiB.
	 *
	 * @link http://www.php.net/manual/en/function.filesize.php#91477
	 * @param int $bytes
	 * @param int $precision
	 * @return string
	 */
	public static function formatByteCount($bytes, $precision = 2) {
		$units = array('bytes', 'KiB', 'MiB', 'GiB', 'TiB'); //SI units.

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$size = $bytes / pow(1024, $pow);

		return round($size, $precision) . ' ' . $units[$pow];
	}

	public function installFixedMessageCleanupJob() {
		if ( !$this->fixedMessageCleanupCronJob->is_scheduled() ) {
			$this->fixedMessageCleanupCronJob->reset();
		}
	}

	public function getPluginFile() {
		return $this->pluginFile;
	}

	public function getRemainingMemory() {
		if ( !function_exists('wp_convert_hr_to_bytes') ) {
			return null;
		}

		$currentLimit = @ini_get('memory_limit');
		$currentLimitBytes = wp_convert_hr_to_bytes($currentLimit);

		if ( $currentLimitBytes === -1 ) {
			return null;
		}

		$remainingBytes = $currentLimitBytes - memory_get_usage();
		return max(0, $remainingBytes);
	}

	public function initSetupWizard() {
		if ( is_admin() && current_user_can('update_core') ) {
			$this->wizard = new Elm_SetupWizard($this);
		}
	}
}