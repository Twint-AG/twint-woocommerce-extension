<?php

class Elm_DashboardWidget {
	protected $widgetId = 'ws_php_error_log';
	protected $requiredCapability = 'manage_options';
	protected $widgetCssPath = 'css/dashboard-widget.css';

	/**
	 * @var scbOptions $settings Plugin settings.
	 */
	protected $settings;
	/**
	 * @var Elm_plugin $plugin A reference to the main plugin object.
	 */
	protected $plugin;

	/**
	 * @var \Ajaw_v1_Action
	 */
	protected $downloadLogAction;

	protected function __construct($settings, $plugin) {
		$this->settings = $settings;
		$this->plugin = $plugin;

		ajaw_v1_CreateAction('elm-ignore-message')
			->handler(array($this, 'ajaxIgnoreMessage'))
			->requiredParam('message')
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-unignore-message')
			->handler(array($this, 'ajaxIgnoreMessage'))
			->requiredParam('message')
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-clear-ignored-messages')
			->handler(array($this, 'ajaxClearAllIgnoredMessages'))
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-mark-as-fixed')
			->handler(array($this, 'ajaxMarkAsFixed'))
			->requiredParam('message')
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-mark-as-not-fixed')
			->handler(array($this, 'ajaxMarkAsFixed'))
			->requiredParam('message')
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-clear-fixed-messages')
			->handler(array($this, 'ajaxClearAllFixedMessages'))
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-hide-pro-notice')
			->handler(array($this, 'ajaxHideUpgradeNotice'))
			->requiredCap($this->requiredCapability)
			->register();

		$this->downloadLogAction = ajaw_v1_CreateAction('elm-download-log')
			->handler(array($this, 'ajaxDownloadLog'))
			->requiredCap($this->requiredCapability)
			->method('GET')
			->register();

		add_action('wp_dashboard_setup', array($this, 'registerWidget'));
		add_action('wp_network_dashboard_setup', array($this, 'registerWidget'));
		add_action('admin_init', array($this, 'handleLogClearing'));
	}

	public function registerWidget() {
		if ( $this->userCanSeeWidget() ) {
			wp_add_dashboard_widget(
				$this->widgetId,
				/* translators: Dashboard widget name */
				__('PHP Error Log', 'error-log-monitor'),
				array($this, 'displayWidgetContents'),
				array($this, 'handleSettingsForm')
			);

			add_action('admin_enqueue_scripts', array($this, 'enqueueWidgetDependencies'));
		}
	}

	private function userCanSeeWidget() {
		return apply_filters('elm_show_dashboard_widget', current_user_can($this->requiredCapability));
	}

	private function userCanClearLog() {
		return $this->userCanSeeWidget() && current_user_can('install_plugins');
	}

	protected function userCanChangeSettings() {
		return $this->userCanSeeWidget() && current_user_can('install_plugins');
	}

	private function userCanDownloadLog() {
		return $this->userCanSeeWidget() && current_user_can('install_plugins');
	}

	public function enqueueWidgetDependencies($hook) {
		if ( $hook === 'index.php' ) {
			wp_enqueue_script(
				'elm-dashboard-widget',
				plugins_url('js/dashboard-widget.js', $this->plugin->getPluginFile()),
				array('jquery', 'ajaw-v1-ajax-action-wrapper'),
				'20240910-3'
			);

			wp_enqueue_style(
				'elm-dashboard-widget-styles',
				plugins_url($this->widgetCssPath, $this->plugin->getPluginFile()),
				array(),
				'20240912'
			);
		}
	}

	public function displayWidgetContents() {
		$log = Elm_PhpErrorLog::autodetect();

		if ( is_wp_error($log) ) {
			$this->displayConfigurationHelp($log->get_error_message());
			return;
		}

		if ( isset($_GET['elm-log-cleared']) && !empty($_GET['elm-log-cleared']) ) {
			printf('<p><strong>%s</strong></p>', __('Log cleared.', 'error-log-monitor'));
		}

		$this->displayContentSection($log);

		if ( $log->getFileSize() > 0 ) {
			echo '<p>';
			printf(
			/* translators: 1: Log file name, 2: Log file size */
				__('Log file: %1$s (%2$s)', 'error-log-monitor') . ' ',
				esc_html($log->getFilename()),
				Elm_Plugin::formatByteCount($log->getFileSize(), 2)
			);

			if ( $this->userCanClearLog() ) {
				/** @noinspection HtmlUnknownTarget */
				printf(
					'<a href="%s" class="button" onclick="return confirm(\'%s\');">%s</a>',
					wp_nonce_url(self_admin_url('/index.php?elm-action=clear-log&noheader=1'), 'clear-log'),
					esc_js(__('Are you sure you want to clear the error log?', 'error-log-monitor')),
					__('Clear Log', 'error-log-monitor')
				);
			}

			if ( $this->userCanDownloadLog() && $this->isCompressedLogDownloadPossible() ) {
				$downloadUrl = add_query_arg(
					[
						'action'      => $this->downloadLogAction->action,
						'_ajax_nonce' => wp_create_nonce($this->downloadLogAction->action),
						'mtime'       => $log->getModificationTime(),
					],
					admin_url('admin-ajax.php')
				);

				/** @noinspection HtmlUnknownTarget */
				printf(
					' <a href="%s" class="button">%s</a>',
					esc_url($downloadUrl),
					__('Download Log', 'error-log-monitor')
				);
			}

			echo '</p>';
		}

		if (
			$this->userCanChangeSettings()
			&& wsh_elm_fs()->is_not_paying()
			&& $this->settings->get('enable_premium_notice', true)
			&& wsh_elm_fs()->is_pricing_page_visible()
			&& (!wsh_elm_fs()->is_activation_mode())
		) {
			echo '<div class="elm-upgrade-to-pro-footer">';

			echo __('Upgrade to Pro for more detailed logs and a summary view.', 'error-log-monitor'), '<br>';

			echo '<div class="elm-upgrade-notice-links">';
			printf(
				'<a href="%s" target="_blank" rel="noopener" title="Opens in a new tab">%s</a>',
				esc_attr('https://errorlogmonitor.com/'),
				_x('Details', 'a link to Pro version information', 'error-log-monitor')
			);
			echo ' | ';
			printf(
				'<a href="%s">%s</a>',
				esc_attr(wsh_elm_fs()->get_upgrade_url()),
				__('View Pricing', 'error-log-monitor')
			);
			echo ' | ';
			printf(
				'<a href="%s" class="elm-hide-upgrade-notice">%s</a>',
				esc_attr('#'),
				_x('Hide', 'a link that hides the "Upgrade to Pro" notice', 'error-log-monitor')
			);
			echo '</div>';

			echo '</div>';
		}

		do_action('elm_after_widget_footer');
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 */
	protected function displayContentSection($log) {
		$this->displayLatestEntries($log);
	}

	/**
	 * @param Elm_PhpErrorLog $log
	 */
	protected function displayLatestEntries($log) {
		$filteredLog = $this->plugin->getWidgetEntries($log);
		if ( is_wp_error($filteredLog) ) {
			printf('<p>%s</p>', $filteredLog->get_error_message());
			return;
		}

		$startTime = microtime(true);
		$lines = $filteredLog->readLastEntries($this->settings->get('widget_line_count'));
		$elapsedTime = microtime(true) - $startTime;

		printf('<!-- Log file parse time: %.3f seconds -->', $elapsedTime);

		if ( empty($lines) ) {
			if ( $filteredLog->getSkippedEntryCount() > 0 ) {
				$message = __(
					'There are no recent log entries that match the filter settings.',
					'error-log-monitor'
				);
			} else {
				$message = __('The log file is empty.', 'error-log-monitor');
			}
			echo '<p>', $message, '</p>';

			if ( $filteredLog->getSkippedEntryCount() > 0 ) {
				$this->displaySkippedEntryCount($filteredLog);
			}
		} else {
			if ( $this->settings->get('sort_order') === 'reverse-chronological' ) {
				$lines = array_reverse($lines);
			}

			/*
			 * Maybe show a notice if there are no errors or warnings in the last 24 hours / 7 days.
			 * Either just no messages, or only minor messages like notices or deprecation warnings.
			 * "No errors or warnings today."
			 * "No log entries in the last 24 hours."
			 * "No errors or warnings in the last 24 hours."
			 */

			if ( $this->settings->get('dashboard_log_layout', 'list') === 'list' ) {
				$this->displayLogAsList($lines);
			} else {
				$this->displayLogAsTable($lines);
			}

			if ( $filteredLog->getSkippedEntryCount() > 0 ) {
				$this->displaySkippedEntryCount($filteredLog);
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getItemActionLinks() {
		return array(
			$this->getMarkAsFixedLink(),
			$this->getIgnoreLink(),
		);
	}

	private function getMarkAsFixedLink() {
		static $html = null;
		if ( $html === null ) {
			$html = sprintf(
				'<a href="#" class="elm-mark-as-fixed" title="%s">%s</a>',
				esc_attr(__(
					"Mark the error as fixed and hide it. If this error happens again in the future, it will become visible again.",
					'error-log-monitor'
				)),
				_x('Mark as fixed', 'action link', 'error-log-monitor')
			);
		}
		return $html;
	}

	private function getIgnoreLink() {
		static $html = null;
		if ( $html === null ) {
			$html = sprintf(
				'<a href="#" class="elm-ignore-message" title="%s">%s</a>',
				esc_attr(__(
					"Ignored messages stay in the log file but they don't show up in the widget and don't generate email notifications.",
					'error-log-monitor'
				)),
				_x('Ignore', 'action link', 'error-log-monitor')
			);
		}
		return $html;
	}

	private function displaySkippedEntryCount(Elm_SeverityFilter $filteredLog) {
		echo '<p><div class="dashicons dashicons-filter" style="color: #82878c;"></div> ';
		$filteredLog->formatSkippedEntryCount();
		echo '</p>';
	}

	private function displayLogAsTable($lines) {
		echo '<table class="widefat striped elm-log-entries elm-log-table">',
		'<colgroup><col style="width: 9em;"><col></colgroup>',
		'<tbody>';
		foreach ($lines as $line) {
			printf(
				'<tr data-raw-message="%s" data-hash="%s" class="elm-entry"><td style="white-space:nowrap;">
						%s
						<p class="elm-line-actions">%s</p>						
					</td>',
				esc_attr($line['message']),
				esc_attr($this->getMessageHash($line['message'])),
				!empty($line['timestamp']) ? $this->plugin->formatTimestamp($line['timestamp']) : '',
				implode(' | ', $this->getItemActionLinks())
			);

			echo '<td>';
			echo esc_html($this->plugin->formatLogMessage($line['message']));

			if ( !empty($line['stacktrace']) ) {
				$this->displayStackTrace($line['stacktrace']);
			}

			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	protected function displayLogAsList($lines, $listClasses = array(), $callbacks = array()) {
		//Do any of the log entries have a stack trace?
		$hasStackTraces = false;
		foreach ($lines as $line) {
			if ( !empty($line['stacktrace']) ) {
				$hasStackTraces = true;
				break;
			}
		}

		$listClasses = array_merge(array('elm-log-entries', 'elm-wide-list'), $listClasses);
		if ( $hasStackTraces ) {
			$listClasses[] = 'elm-list-with-stack-traces';
		}

		$actions = sprintf('<div class="elm-line-actions">%s</div>', implode(' | ', $this->getItemActionLinks()));

		echo '<ul class="', esc_attr(implode(' ', $listClasses)), '">';
		foreach ($lines as $line) {
			$itemClasses = array('elm-entry');
			if ( !empty($line['stacktrace']) ) {
				$itemClasses[] = 'elm-has-stack-trace';
			}
			if ( isset($line['level']) ) {
				$itemClasses[] = 'elm-level-' . preg_replace('@[^a-z\-]@', '-', $line['level']);
			}

			$messageHash = $this->getMessageHash($line['message']);

			printf(
				'<li class="%s" data-raw-message="%s" data-hash="%s">',
				esc_attr(implode(' ', $itemClasses)),
				esc_attr($line['message']),
				esc_attr($messageHash)
			);

			echo '<div class="elm-entry-body-container">';
			echo '<div class="elm-entry-body">', $actions;

			if ( isset($callbacks['displayMetadata']) ) {
				call_user_func($callbacks['displayMetadata'], $line);
			} else {
				printf(
					'<p class="elm-entry-metadata"><span class="elm-severity-bubble">&nbsp;</span>'
					. ' <span class="elm-timestamp" title="%s">%s</span></p>',
					!empty($line['timestamp']) ? gmdate('Y-m-d H:i:s e', $line['timestamp']) : '',
					!empty($line['timestamp']) ? $this->plugin->formatTimestamp($line['timestamp']) : ''
				);
			}

			if ( isset($callbacks['beforeMessage']) ) {
				call_user_func($callbacks['beforeMessage'], $line);
			}

			$message = $this->plugin->formatLogMessage($line['message'], isset($line['level']) ? $line['level'] : null);
			$message = esc_html($message);
			$message = nl2br($this->insertCodeBreaks($message));
			echo '<p class="elm-log-message">', $message, '</p>';

			echo '</div>'; //.elm-entry-body
			echo '</div>'; //.elm-entry-body-container

			echo '<div class="elm-entry-context-container">';
			if ( isset($callbacks['inContext']) ) {
				call_user_func($callbacks['inContext'], $line, $messageHash);
			}

			if ( isset($line['context'], $line['context']['stackTrace']) ) {
				$this->displayStackTrace($line['context']['stackTrace'], $messageHash);
			} else if ( !empty($line['stacktrace']) ) {
				$this->displayStackTrace($line['stacktrace'], $messageHash);
			}

			if ( !empty($line['context']) ) {
				$this->displayContextData($line['context'], $messageHash);
			}
			echo '</div>';

			echo '</li>';
		}
		echo '</ul>';
	}

	private function displayStackTrace($stackTrace, $messageHash = '') {
		if ( empty($stackTrace) ) {
			return;
		}

		$firstLine = reset($stackTrace);
		if ( is_string($firstLine) ) {
			//This is a plain PHP/XDebug stack trace.
			//Skip the opening "Stack trace:" line. There will be a heading with the same text.
			$firstLine = strtolower(trim($firstLine, ' :'));
			if ( ($firstLine === 'stack trace') || ($firstLine === 'php stack trace') ) {
				array_shift($stackTrace);
			}
		}

		$isMundane = $this->findMundaneTraceItems($stackTrace);

		$groupClasses = array('elm-context-group', 'elm-collapsible-context-group');
		if ( !$this->isContextGroupOpen('stackTrace', $messageHash) ) {
			$groupClasses[] = 'elm-closed-context-group';
		}

		echo '<div class="' . esc_attr(implode(' ', $groupClasses)) . '" data-group="stackTrace">
			<h3 class="elm-context-group-caption">', __('Stack Trace', 'error-log-monitor'), '</h3>
			<table class="elm-context-group-content elm-hide-mundane-items elm-stacktrace">';

		$visibleRowNumber = 0;

		foreach ($stackTrace as $index => $item) {
			$classes = array();

			if ( !empty($isMundane[$index]) ) {
				$classes[] = 'elm-is-mundane';
			} else {
				$visibleRowNumber++;
				if ( $visibleRowNumber % 2 === 1 ) {
					$classes[] = 'elm-is-odd-visible-row';
				}
				if ( $visibleRowNumber === 1 ) {
					$classes[] = 'elm-first-non-mundane-item';
				}
			}

			if ( is_string($item) ) {
				//Remove the "PHP " prefix from trace items.
				$item = preg_replace('@^PHP @', '', $item, 1);
				printf(
					'<tr class="%s"><td colspan="2"><span class="elm-stack-frame-content">%s</span></td></tr>' . "\n",
					implode(' ', $classes),
					$this->insertPathBreaks(esc_html($this->plugin->formatLogMessage($item)))
				);
				continue;
			}

			printf('<tr class="%s">', implode(' ', $classes));
			printf('<td class="elm-stack-frame-index">%d.</td>', $index + 1);

			echo '<td class="elm-stack-frame-content">';
			if ( !empty($item['call']) ) {
				printf('<span class="elm-function-call">%s</span>', esc_html($item['call']));
			}

			if ( !empty($item['file']) ) {
				printf(
					'<span class="elm-code-location"><span class="elm-code-file-name">%s</span>'
					. ':<span class="elm-line-number">%d</span></span>',
					$this->insertPathBreaks(esc_html($this->plugin->formatLogMessage($item['file']))),
					isset($item['line']) ? $item['line'] : 0
				);
			}
			echo '</td>';
			echo '</tr>';

		}

		$mundaneItems = array_sum($isMundane);
		if ( $mundaneItems > 0 ) {
			echo '<tr class="elm-more-context-row"><td colspan="2"><a href="#" class="elm-show-mundane-context">';
			printf(
				_x('Show %d more', 'show hidden stack trace items', 'error-log-monitor'),
				$mundaneItems
			);
			echo '</a></td></tr>';
		}

		echo '</table></div>';
	}

	protected function insertPathBreaks($text) {
		return preg_replace('@(\?|\(|[/\\\]++)@', '<wbr>$1', $text);
	}

	protected function insertCodeBreaks($text) {
		$text = preg_replace('@(->|[./\\\]++)@', '<wbr>$1', $text);
		$text = preg_replace('@([()\[\]])@', '$1<wbr>', $text);
		return $text;
	}

	/**
	 * Filter a stack trace and decide which items should be hidden because they're mundane/uninteresting.
	 *
	 * Returns an array with the same indexes as the input. The values indicate if the corresponding stack
	 * trace item is mundane or not:
	 *  0 = not mundane, leave it visible.
	 *  1 = mundane or irrelevant, hide it.
	 *
	 * The returned array might be smaller than the input and it could be missing some indexes.
	 * Assume that the default is 0 (i.e. not mundane).
	 *
	 * @param array $stackTrace A stack trace.
	 * @return int[]
	 */
	protected function findMundaneTraceItems($stackTrace) {
		$isMundane = array();
		//If there are 4 or fewer items, just show them all.
		if ( count($stackTrace) <= 4 ) {
			return $isMundane;
		}

		//Always show the first and last item.
		$isMundane[0] = 0;
		$isMundane[count($stackTrace) - 1] = 0;
		foreach ($stackTrace as $index => $item) {
			if ( is_string($item) || isset($isMundane[$index]) ) {
				continue;
			}

			$fileName = isset($item['file']) ? str_replace('\\', '/', $item['file']) : '';

			if ( $fileName && (strpos($fileName, '/wp-content/') !== false) ) {
				//Show any items where the file is part of a plugin or theme (check for 'wp-content').
				$isMundane[$index] = 0;
			} else if (
				(!empty($item['call']) && $this->startsWith($item['call'], 'WP_CLI\\'))
				|| (strpos($fileName, '/wp-cli.phar/') !== false)
			) {
				//Hide everything in the WP_CLI namespace.
				$isMundane[$index] = 1;
			} else if ( $fileName === '' ) {
				//Show items that don't have a file name.
				$isMundane[$index] = 0;
			} else if (
				!empty($item['call'])
				&& (strpos($item['call'], 'WP_Hook->') !== false)
				&& (strpos($fileName, '/wp-includes/') !== false)
			) {
				//Hide calls involving the "WP_Hook" class if the file is part of WordPress core.
				$isMundane[$index] = 1;
			} else if ( $this->endsWithAny(
				$fileName,
				array(
					'/wp-load.php',
					'/wp-config.php',
					'/wp-settings.php',
					'/wp-admin/admin.php',
					'/wp-admin/menu.php',
					'/wp-includes/template-loader.php',
					'/wp-blog-header.php',
					'/wp-includes/template.php',
				)
			) ) {
				//Hide core files that are included on almost every page load.
				$isMundane[$index] = 1;
			}
		}

		//If there are fewer than 2 hidden items, just show them all.
		if ( array_sum($isMundane) < 2 ) {
			$isMundane = array();
		}

		//Always show at least 3 items. Show the second one if there are fewer than 3 visible.
		$visibleItems = count($stackTrace) - array_sum($isMundane);
		if ( ($visibleItems < 3) && (count($stackTrace) >= 3) && isset($stackTrace[1]) ) {
			$isMundane[1] = 0;
		}

		return $isMundane;
	}

	protected function startsWith($string, $prefix) {
		if ( !isset($string, $prefix) ) {
			return false;
		}
		return (substr($string, 0, strlen($prefix)) === $prefix);
	}

	protected function endsWith($string, $suffix) {
		if ( !isset($string, $suffix) ) {
			return false;
		}
		return (substr($string, -strlen($suffix)) === $suffix);
	}

	protected function endsWithAny($string, $suffixList) {
		foreach ($suffixList as $suffix) {
			if ( $this->endsWith($string, $suffix) ) {
				return true;
			}
		}
		return false;
	}

	protected function displayContextData($context, $messageHash = '') {
		//The free version doesn't collect context data.
		//This method is an extension point for other versions.
	}

	protected function isContextGroupOpen($groupName, $messageHash = ''): bool {
		if ( $messageHash ) {
			$state = $this->getMessageGroupState($groupName, $messageHash);
			if ( $state !== null ) {
				return $state;
			}
		}

		$visibility = $this->settings->get('context_group_visibility', array());
		//By default all groups are visible (= open).
		if ( !isset($visibility[$groupName]) ) {
			return true;
		}
		return !empty($visibility[$groupName]);
	}

	protected function getAvailableContextGroups(): array {
		return array('stackTrace' => __('Stack Trace', 'error-log-monitor'));
	}

	private $isHashAlgorithmAvailable = null;
	const MESSAGE_HASH_LENGTH = 10;
	const GROUP_STATE_COOKIE = 'elm_context_group_state';

	protected function getMessageHash($message) {
		if ( empty($message) || !is_string($message) ) {
			return '';
		}

		if ( $this->isHashAlgorithmAvailable === null ) {
			$this->isHashAlgorithmAvailable =
				function_exists('hash')
				&& in_array('xxh3', hash_algos());
		}

		if ( $this->isHashAlgorithmAvailable ) {
			$hash = hash('xxh3', $message, false);
		} else {
			$hash = md5($message);
		}
		return substr($hash, 0, self::MESSAGE_HASH_LENGTH);
	}

	/**
	 * @var null|array
	 */
	private $cacheMessageGroupState = null;

	private function getMessageGroupState($group, $messageHash) {
		if ( $this->cacheMessageGroupState === null ) {
			$this->cacheMessageGroupState = array();
			$cookie = isset($_COOKIE[self::GROUP_STATE_COOKIE]) ? stripslashes($_COOKIE[self::GROUP_STATE_COOKIE]) : '';
			if ( $cookie ) {
				$cookieData = json_decode($cookie, true);
				//The cookie is expected to contain an array of [key, boolean] pairs.
				if ( is_array($cookieData) ) {
					foreach ($cookieData as $item) {
						if ( isset($item[0], $item[1]) && is_string($item[0]) ) {
							$this->cacheMessageGroupState[$item[0]] = (bool)$item[1];
						}
					}
				}
			}
		}

		$key = $messageHash . ':' . $group;
		if ( isset($this->cacheMessageGroupState[$key]) ) {
			return (bool)$this->cacheMessageGroupState[$key];
		}
		return null;
	}

	private function displayConfigurationHelp($problem) {
		$exampleCode = "@ini_set('log_errors', 'On');\n"
			. "@ini_set('error_log', '/full/path/to/php-errors.log');";
		printf('<p><strong>%s</strong></p>', $problem);

		echo '<p>';
		_e(
			'To enable error logging, create an empty file named "php-errors.log".
			Place it in a directory that is not publicly accessible (preferably outside
			your web root) and ensure it is writable by the web server.
			Then add the following code to <code>wp-config.php</code>:',
			'error-log-monitor'
		);
		echo '</p>';
		echo '<pre>', $exampleCode, '</pre>';

		if ( !WP_DEBUG ) {
			echo '<p>';
			_e(
				'By default, only fatal errors and warnings will be logged. To also log notices
                and other messages, enable the <code>WP_DEBUG</code> option by adding this code:',
				'error-log-monitor'
			);
			echo '</p>';
			$debugCode =
				"//Report all types of errors.\n"
				. "define('WP_DEBUG', true);\n"
				. "//Don't show errors to site visitors.\n"
				. "define('WP_DEBUG_DISPLAY', false);";
			echo '<pre>', $debugCode, '</pre>';
		}

		echo '<p>';
		printf(
			__('For reference, the full path of the WordPress directory is:<br>%s', 'error-log-monitor'),
			'<code>' . htmlentities(ABSPATH) . '</code>'
		);
		echo '</p>';

		echo '<p>';
		printf(
		/* translators: Links to English-language articles about configuring error logging. */
			__('See also: %s', 'error-log-monitor'),
			'<a href="https://codex.wordpress.org/Editing_wp-config.php#Configure_Error_Logging">Editing wp-config.php</a>,
			 <a href="https://digwp.com/2009/07/monitor-php-errors-wordpress/">3 Ways To Monitor PHP Errors</a>'
		);
		echo '</p>';
	}

	public function handleSettingsForm() {
		if ( !$this->userCanChangeSettings() ) {
			_e("Sorry, you are not allowed to change these settings.", 'error-log-monitor');

			//Hide the "Submit" button. WordPress doesn't provide any way to remove it completely.
			echo '<style>#ws_php_error_log .submit { display: none; }</style>';

			return;
		}

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget_id']) && is_array($_POST[$this->widgetId]) ) {
			$formInputs = $_POST[$this->widgetId];

			//Remove magic quotes. There's no hook for wp_magic_quotes, so we use one that's
			//close in execution order.
			if ( did_action('sanitize_comment_cookies') && function_exists('wp_magic_quotes') ) {
				$formInputs = wp_unslash($formInputs);
			}

			$settingsErrors = array();
			/** @var Elm_ConfigurationError[] $settingsErrors */

			$this->settings->set('widget_line_count', intval($formInputs['widget_line_count']));
			if ( $this->settings->get('widget_line_count') <= 0 ) {
				$this->settings->set('widget_line_count', $this->settings->get_defaults('widget_line_count'));
			}

			$this->settings->set('strip_wordpress_path', isset($formInputs['strip_wordpress_path']));

			$emails = $this->parseNotificationEmailList(strval($formInputs['send_errors_to_email']));
			$this->settings->set('send_errors_to_email', $emails);

			$this->settings->set('email_interval', intval($formInputs['email_interval']));
			if ( $this->settings->get('email_interval') <= 60 ) {
				$this->settings->set('email_interval', $this->settings->get_defaults('email_interval'));
			}

			$this->settings->set('email_log_check_interval', intval($formInputs['email_log_check_interval']));
			//We must check the log at least as often as email_interval or email_interval will never be reached.
			if ( $this->settings->get('email_log_check_interval') > $this->settings->get('email_interval') ) {
				$this->settings->set('email_log_check_interval', $this->settings->get('email_interval'));
			}
			if ( $this->settings->get('email_log_check_interval') <= 60 ) {
				$this->settings->set('email_log_check_interval', $this->settings->get_defaults('email_log_check_interval'));
			}

			if ( isset($formInputs['sort_order']) ) {
				$this->settings->set('sort_order', strval($formInputs['sort_order']));
				if ( !in_array($this->settings->get('sort_order'), array('chronological', 'reverse-chronological')) ) {
					$this->settings->set('sort_order', $this->settings->get_defaults('sort_order'));
				}
			}

			$enableLogSizeNotification = isset($formInputs['enable_log_size_notification']);
			//Reset the "notification sent" flag when the user turns notifications on/off.
			if ( $enableLogSizeNotification != $this->settings->get('enable_log_size_notification') ) {
				//This is useful for testing and situations where the flag was set and then notifications got
				//temporarily turned off, with the log file size changing in the meantime.
				$this->settings->set('log_size_notification_sent', false);
			}
			$this->settings->set('enable_log_size_notification', $enableLogSizeNotification);

			if (
				$this->settings->get('enable_log_size_notification')
				&& isset($formInputs['log_size_notification_threshold'])
			) {
				$this->settings->set(
					'log_size_notification_threshold',
					floatval($formInputs['log_size_notification_threshold']) * Elm_Plugin::MB_IN_BYTES
				);
			}

			$dashboardFilterMode = strval($formInputs['dashboard_message_filter']);
			if ( in_array($dashboardFilterMode, array('all', 'selected')) ) {
				$this->settings->set('dashboard_message_filter', $dashboardFilterMode);

				if ( $dashboardFilterMode === 'selected' ) {
					$this->settings->set(
						'dashboard_message_filter_groups',
						$this->parseSelectedSeverityOptions($formInputs, 'dashboard_severity_option')
					);
				}
			}

			$emailFilterMode = strval($formInputs['email_message_filter']);
			if ( in_array($emailFilterMode, array('same_as_dashboard', 'selected')) ) {
				$this->settings->set('email_message_filter', $emailFilterMode);
				if ( $emailFilterMode === 'selected' ) {
					$this->settings->set(
						'email_message_filter_groups',
						$this->parseSelectedSeverityOptions($formInputs, 'email_severity_option')
					);
				}
			}

			if ( isset($formInputs['regex_filter_text']) ) {
				$regexFilterText = strval($formInputs['regex_filter_text']);
			} else {
				$regexFilterText = '';
			}
			$this->settings->set('regex_filter_text', $regexFilterText);

			$regexListParser = new Elm_RegexListParser();
			$regexData = $regexListParser->parse($regexFilterText);
			$settingsErrors = array_merge($settingsErrors, $regexData->getErrors());
			$this->settings->set('regex_filter_patterns', $regexData->getValidPatterns());

			$logLayout = strval($formInputs['dashboard_log_layout']);
			if ( in_array($logLayout, array('list', 'table')) ) {
				$this->settings->set('dashboard_log_layout', $logLayout);
			}

			$contextGroups = array_keys($this->getAvailableContextGroups());
			$visibilitySettings = array();
			foreach ($contextGroups as $group) {
				$groupFieldName = 'context_visibility_option-' . $group;
				$visibilitySettings[$group] = !empty($formInputs[$groupFieldName]);
			}
			$this->settings->set('context_group_visibility', $visibilitySettings);

			do_action('elm_handle_widget_settings_form', $formInputs);
			do_action('elm_settings_changed', $this->settings);

			if ( !empty($settingsErrors) ) {
				$this->handleSettingsErrors($settingsErrors);
			}
		}

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('General', 'configuration section heading', 'error-log-monitor')
		);

		printf(
			'<p><label>%s <br><input type="text" name="%s[widget_line_count]" value="%s" size="5"></label></p>',
			__('Number of entries to show:', 'error-log-monitor'),
			esc_attr($this->widgetId),
			esc_attr($this->settings->get('widget_line_count'))
		);

		printf(
			'<p><label><input type="checkbox" name="%s[strip_wordpress_path]" %s> %s</label></p>',
			esc_attr($this->widgetId),
			$this->settings->get('strip_wordpress_path') ? ' checked="checked"' : '',
			__('Strip WordPress root directory from log messages', 'error-log-monitor')
		);

		printf(
			'<p><label><input type="checkbox" name="%s[sort_order]" value="reverse-chronological" %s> %s</label></p>',
			esc_attr($this->widgetId),
			$this->settings->get('sort_order') === 'reverse-chronological' ? ' checked="checked"' : '',
			__('Reverse line order (most recent on top)', 'error-log-monitor')
		);

		printf('<p class="hidden">%s <br>', __('Widget layout:', 'error-log-monitor'));
		$layouts = array(
			_x('Table', 'widget layout option', 'error-log-monitor') => 'table',
			_x('List', 'widget layout option', 'error-log-monitor')  => 'list',
		);
		foreach ($layouts as $name => $value) {
			printf(
				'<label><input type="radio" name="%s[dashboard_log_layout]" value="%s" %s> %s</label><br>',
				esc_attr($this->widgetId),
				esc_attr($value),
				($value == $this->settings->get('dashboard_log_layout')) ? ' checked="checked"' : '',
				$name
			);
		}
		echo '</p>';

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('Notifications', 'configuration section heading', 'error-log-monitor')
		);

		$emails = $this->settings->get('send_errors_to_email');
		if ( !is_array($emails) ) {
			$emails = $this->parseNotificationEmailList($emails);
		}
		printf(
			'<p>
				<label for="%1$s-send_errors_to_email">%2$s</label>
				<input type="text" class="widefat" name="%1$s[send_errors_to_email]" id="%1$s-send_errors_to_email" 
				       value="%3$s"
				       title="%4$s">
			</p>',
			esc_attr($this->widgetId),
			__('Periodically email logged errors to:', 'error-log-monitor'),
			implode(', ', $emails),
			_x(
				'You can enter multiple emails by separating them with a comma.',
				'tooltip for the email field',
				'error-log-monitor'
			)
		);

		$intervals = array(
			__('Every 2 minutes', 'error-log-monitor')  => 2 * 60,
			__('Every 5 minutes', 'error-log-monitor')  => 5 * 60,
			__('Every 10 minutes', 'error-log-monitor') => 10 * 60,
			__('Every 15 minutes', 'error-log-monitor') => 15 * 60,
			__('Every 30 minutes', 'error-log-monitor') => 30 * 60,
			__('Hourly', 'error-log-monitor')           => 60 * 60,
			__('Daily', 'error-log-monitor')            => 24 * 60 * 60,
			__('Weekly', 'error-log-monitor')           => 7 * 24 * 60 * 60,
		);

		printf(
			'<p><label>%s <br><select name="%s[email_log_check_interval]">',
			__('How often to check the log for new messages:', 'error-log-monitor'),
			esc_attr($this->widgetId)
		);
		$logCheckInterval = min($this->settings->get('email_interval'), $this->settings->get('email_log_check_interval'));
		foreach ($intervals as $name => $interval) {
			printf(
				'<option value="%d" %s>%s</option>',
				$interval,
				($interval == $logCheckInterval) ? ' selected="selected"' : '',
				$name
			);
		}
		echo '</select></label></p>';

		printf(
			'<p><label>%s <br><select name="%s[email_interval]">',
			__('How often to send email (max):', 'error-log-monitor'),
			esc_attr($this->widgetId)
		);
		foreach ($intervals as $name => $interval) {
			printf(
				'<option value="%d" %s>%s</option>',
				$interval,
				($interval == $this->settings->get('email_interval')) ? ' selected="selected"' : '',
				$name
			);
		}
		echo '</select></label></p>';

		do_action('elm_after_email_interval_field');

		printf(
			'<p><label><input type="checkbox" name="%s[enable_log_size_notification]" 
		                      id="elm_enable_log_size_notification" %s> %s</label><br>',
			esc_attr($this->widgetId),
			$this->settings->get('enable_log_size_notification') ? ' checked="checked"' : '',
			__('Send an email notification when the log file size exceeds this limit:', 'error-log-monitor')
		);
		printf(
			'<input type="number" name="%s[log_size_notification_threshold]" value="%s" 
			        size="5" min="1" max="10240" style="max-width: 80px;" 
			        id="elm_log_size_notification_threshold" %s> MiB',
			esc_attr($this->widgetId),
			$this->settings->get('log_size_notification_threshold') / Elm_Plugin::MB_IN_BYTES,
			$this->settings->get('enable_log_size_notification') ? '' : ' disabled="disabled"'
		);
		echo '</p>';

		//This script is too short to be worth placing in a separate file. Let's just inline it.
		?>
		<script type="text/javascript">
			jQuery(function ($) {
				var sizeNotificationEnabled = $('#elm_enable_log_size_notification');
				sizeNotificationEnabled.change(function () {
					$('#elm_log_size_notification_threshold').prop('disabled', !sizeNotificationEnabled.is(':checked'));
				});
			});
		</script>
		<?php

		do_action('elm_after_notification_settings');

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('Filters', 'configuration section heading', 'error-log-monitor')
		);

		echo '<h4>', __('Dashboard widget filter', 'error-log-monitor'), '</h4>';

		printf(
			'<label><input type="radio" name="%s[dashboard_message_filter]" value="all"
			               id="elm_dashboard_message_filter_all" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('dashboard_message_filter', 'all') === 'all') ? ' checked="checked"' : '',
			__('Show all messages', 'error-log-monitor')
		);
		printf(
			'<label><input type="radio" name="%s[dashboard_message_filter]" value="selected"
			               id="elm_dashboard_message_filter_selected" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('dashboard_message_filter') === 'selected') ? ' checked="checked"' : '',
			__('Show only selected types', 'error-log-monitor')
		);

		$this->printSeverityFilterOptions(
			'dashboard_severity_option',
			$this->settings->get('dashboard_message_filter_groups', Elm_SeverityFilter::getAvailableOptions())
		);

		echo '<h4>', __('Email notification filter', 'error-log-monitor'), '</h4>';

		printf(
			'<label><input type="radio" name="%s[email_message_filter]" value="same_as_dashboard"
			               id="elm_email_message_filter_same" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('email_message_filter', 'same_as_dashboard') === 'same_as_dashboard') ? ' checked="checked"' : '',
			__('Same as the dashboard widget', 'error-log-monitor')
		);
		printf(
			'<label><input type="radio" name="%s[email_message_filter]" value="selected"
			               id="elm_email_message_filter_selected" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('email_message_filter') === 'selected') ? ' checked="checked"' : '',
			__('Notify only about selected types', 'error-log-monitor')
		);

		$this->printSeverityFilterOptions(
			'email_severity_option',
			$this->settings->get('email_message_filter_groups', Elm_SeverityFilter::getAvailableOptions())
		);

		//Ignored message list.
		printf('<h4>%s</h4>', __('Ignored messages', 'error-log-monitor'));

		$ignoredMessages = $this->settings->get('ignored_messages', array());

		$noMessagesNotice = sprintf(
			'<p id="elm-no-ignored-messages-notice" style="%s">%s</p>',
			!empty($ignoredMessages) ? 'display: none;' : '',
			__('There are no ignored messages.', 'error-log-monitor')
		);
		echo $noMessagesNotice;

		if ( !empty($ignoredMessages) ) {
			printf(
				'<button class="button button-small" id="elm-clear-ignored-messages" data-progress-text="%s">%s</button>',
				esc_attr(_x(
					'Processing...',
					'progress text when clearing ignored messages',
					'error-log-monitor'
				)),
				_x('Clear Ignored Messages', 'button title', 'error-log-monitor')
			);

			echo '<table class="widefat striped elm-ignored-messages">';
			foreach (array_keys($ignoredMessages) as $message) {
				printf('<tr data-raw-message="%s">', esc_attr($message));
				printf('<td>%s</td>', htmlentities($message));
				printf(
					'<td><p class="elm-line-actions"><a href="#" class="elm-unignore-message">%s</a></p></td>',
					_x('Unignore', 'action link', 'error-log-monitor')
				);
				echo '</tr>';
			}
			echo '</table>';
		}

		printf('<h4>%s</h4>', _x('Marked as fixed', 'table heading', 'error-log-monitor'));

		$fixedMessages = $this->settings->get('fixed_messages', array());

		printf(
			'<p id="elm-no-fixed-messages-notice" style="%s">%s</p>',
			!empty($fixedMessages) ? 'display: none;' : '',
			__('No messages have been marked as fixed.', 'error-log-monitor')
		);

		if ( !empty($fixedMessages) ) {
			printf(
				'<button class="button button-small" id="elm-clear-fixed-messages" data-progress-text="%s">%s</button>',
				esc_attr(_x(
					'Processing...',
					'progress text when clearing fixed messages',
					'error-log-monitor'
				)),
				_x('Clear Fixed Messages', 'button title', 'error-log-monitor')
			);

			echo '<table class="widefat striped elm-fixed-messages">';
			foreach ($fixedMessages as $message => $details) {
				printf('<tr data-raw-message="%s">', esc_attr($message));
				printf('<td>%s</td>', htmlentities($message));
				printf(
					'<td><p class="elm-line-actions"><a href="#" class="elm-mark-as-not-fixed">%s</a></p></td>',
					_x('Unmark', 'action link: mark an error as not fixed', 'error-log-monitor')
				);
				echo '</tr>';
			}
			echo '</table>';
		}

		echo '<h4>', __('Ignored regular expressions', 'error-log-monitor'), '</h4>';

		$regexText = $this->settings->get('regex_filter_text', '');
		$regexLines = preg_split('@\R@', $regexText, 1000);
		$currentLineCount = is_array($regexLines) ? count($regexLines) : 0;

		/* translators: help text for the "Ignored regular expressions" field in widget configuration */
		$helpText = __(
			'One regex pattern per line. Messages that match any of the patterns will be hidden.',
			'error-log-monitor'
		);

		//Disable spellcheck and add-ons like Grammarly for this field. They would just
		//report false spelling/grammar errors that don't apply to regex syntax.
		printf(
			'<textarea name="%1$s[regex_filter_text]" spellcheck="false" rows="%4$d" 
				data-enable-grammarly="false" data-gramm_editor="false"
				id="elm_regex_filter_text" placeholder="%2$s" title="%2$s">%3$s</textarea>',
			esc_attr($this->widgetId),
			esc_attr($helpText),
			esc_html($regexText),
			//Adjust box height to match the number of lines of text (within limits).
			min(max($currentLineCount, 3), 20)
		);

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('Context', 'configuration section heading', 'error-log-monitor')
		);

		echo '<p id="elm-group-visibility-options">';
		_e('Expand these sections by default (if available):', 'error-log-monitor');
		echo '<br>';

		foreach ($this->getAvailableContextGroups() as $id => $label) {
			printf(
				'<label><input type="checkbox" name="%s[%s]" %s> %s</label><br>',
				esc_attr($this->widgetId),
				esc_attr('context_visibility_option-' . $id),
				$this->isContextGroupOpen($id) ? ' checked="checked"' : '',
				$label
			);
		}
		echo '</p>';

		if ( !wsh_elm_fs()->is_activation_mode() ) {
			echo '<div id="elm-pro-version-settings-section">';
			printf(
				'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
				_x(
					'Pro Version',
					'the heading of the upgrade-to-pro section in widget settings',
					'error-log-monitor'
				)
			);
			$this->displayProSection();
			echo '</div>';
		}
	}

	private function handleSettingsErrors($errors) {
		if ( empty($errors) ) {
			return;
		}

		//WordPress does not provide an officially approved way to display error messages
		//when saving widget settings, so we'll just stop execution with an error. Note that
		//valid settings will still be saved when this happens.
		$messages = array();

		foreach ($errors as $error) {
			if ( is_string($error) ) {
				$messages[] = $error;
			} else {
				$messages[] = sprintf('<p>%s</p>', $error->getHtml());
			}
		}

		//Include a link back to the widget configuration screen.
		$configUrl = add_query_arg(
			array(
				'edit'         => $this->widgetId,
				//Add a random parameter to force navigation. Without it, the link URL would
				//be exactly the same as the current URL (including the #fragment), and the
				//browser would stay on the error page.
				'_elm-refresh' => rand() . '_' . time(),
			),
			self_admin_url('index.php')
		);
		$configUrl .= '#' . urlencode($this->widgetId);

		wp_die(
			implode("\n", $messages),
			_x(
				'Configuration Error',
				'page title after trying to save an invalid widget configuration',
				'error-log-monitor'
			),
			array(
				'link_url'  => $configUrl,
				'link_text' => __('&laquo; Back to widget configuration', 'error-log-monitor'),
			)
		);

	}

	/**
	 * Parse and validate a comma-separated list of notification email addresses.
	 *
	 * @param string $commaSeparatedList
	 * @return string[]
	 */
	private function parseNotificationEmailList($commaSeparatedList) {
		$emails = array_map('trim', explode(',', $commaSeparatedList));
		$emails = array_filter($emails, array($this, 'looksLikeAnEmail'));
		$emails = array_slice($emails, 0, Elm_Plugin::MAX_NOTIFICATION_EMAIL_ADDRESSES);
		return $emails;
	}

	/**
	 * Extremely basic email validation.
	 *
	 * @param string $text
	 * @return bool
	 */
	private function looksLikeAnEmail($text) {
		return is_string($text) && (strlen($text) >= 3) && (strpos($text, '@') !== false);
	}

	private function printSeverityFilterOptions($fieldNamePrefix, $selectedOptions) {
		echo '<div class="elm_error_type_list" style="margin-left: 18px; margin-bottom: 1em;">';

		foreach (Elm_SeverityFilter::getAvailableOptions() as $errorGroup) {
			$label = ucwords($errorGroup);
			if ( $errorGroup === Elm_SeverityFilter::UNKNOWN_LEVEL_GROUP ) {
				$label = _x('Other', 'error type', 'error-log-monitor');
			}

			printf(
				'<label><input type="checkbox" name="%s[%s-%s]" %s> %s</label><br>',
				esc_attr($this->widgetId),
				esc_attr($fieldNamePrefix),
				$this->slugifyErrorLevel($errorGroup),
				in_array($errorGroup, $selectedOptions) ? ' checked="checked"' : '',
				$label
			);
		}

		echo '</div>';
	}

	private function slugifyErrorLevel($type) {
		return str_replace(' ', '_', $type);
	}

	private function parseSelectedSeverityOptions($formInputs, $fieldPrefix) {
		$validGroups = Elm_SeverityFilter::getAvailableOptions();
		$includedGroups = array();

		foreach ($validGroups as $group) {
			$fieldName = $fieldPrefix . '-' . $this->slugifyErrorLevel($group);
			if ( isset($formInputs[$fieldName]) && !empty($formInputs[$fieldName]) ) {
				$includedGroups[] = $group;
			}
		}

		return $includedGroups;
	}

	public function handleLogClearing() {
		$doClearLog = isset($_GET['elm-action']) && ($_GET['elm-action'] === 'clear-log')
			&& check_admin_referer('clear-log') && $this->userCanClearLog();

		if ( $doClearLog ) {
			$log = Elm_PhpErrorLog::autodetect();
			if ( is_wp_error($log) ) {
				return;
			}

			$log->clear();

			//Since the log is empty now, we can reset the file size notification.
			$this->settings->set('log_size_notification_sent', false);

			wp_redirect(self_admin_url('index.php?elm-log-cleared=1'));
			exit();
		}
	}

	private function isCompressedLogDownloadPossible() {
		return (
			//Check ZipStream-PHP requirements.
			//PHP 8.1 or later
			version_compare(phpversion(), '8.1.0', '>=')
			//64-bit PHP
			&& (PHP_INT_SIZE === 8)
			//zlib extension
			&& extension_loaded('zlib')
			//mbstring extension
			&& extension_loaded('mbstring')
			//To avoid compatibility issues, abort if another plugin has already loaded ZipStream.
			//It could be a different version or a fork with incompatible changes.
			&& !class_exists('ZipStream\\ZipStream', false)
		);
	}

	private function setupZipStreamAutoloader() {
		static $autoloaderRegistered = false;
		if ( $autoloaderRegistered ) {
			return;
		}

		$psr4Autoloader = function ($class) {
			$prefix = 'ZipStream\\';
			$baseDir = __DIR__ . '/../vendor/ZipStream-PHP/src/';

			//Does the class use the namespace prefix?
			$len = strlen($prefix);
			if ( strncmp($prefix, $class, $len) !== 0 ) {
				return;
			}

			$relativeClass = substr($class, $len);
			$filePath = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
			if ( file_exists($filePath) ) {
				require $filePath;
			}
		};

		spl_autoload_register($psr4Autoloader);
		$autoloaderRegistered = true;
	}

	/**
	 * Note: This handler processes both "ignore" and "unignore" operations.
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function ajaxIgnoreMessage($params) {
		return $this->plugin->updateMessageBlacklist(
			'ignored_messages',
			$params['message'],
			$params['action'] === 'elm-ignore-message',
			true,
			'elm_ignored_status_changed'
		);
	}

	public function ajaxMarkAsFixed($params) {
		return $this->plugin->updateMessageBlacklist(
			'fixed_messages',
			$params['message'],
			$params['action'] === 'elm-mark-as-fixed',
			array('isFixed' => true, 'fixedOn' => time()),
			'elm_fixed_status_changed'
		);
	}

	public function ajaxClearAllIgnoredMessages() {
		$items = $this->settings->get('ignored_messages');
		$total = count($items);
		foreach (array_keys($items) as $message) {
			$this->plugin->queueBlacklistRemoval(
				'ignored_messages',
				$message,
				'elm_ignored_status_changed'
			);
		}
		$this->plugin->flushBlacklistChanges();

		return array('removedItems' => $total);
	}

	public function ajaxClearAllFixedMessages() {
		$items = $this->settings->get('fixed_messages');
		$total = count($items);
		foreach (array_keys($items) as $message) {
			$this->plugin->queueBlacklistRemoval(
				'fixed_messages',
				$message,
				'elm_fixed_status_changed'
			);
		}
		$this->plugin->flushBlacklistChanges();

		return array('removedItems' => $total);
	}

	public function displayProSection() {
		if ( !current_user_can('manage_options') ) {
			return;
		}

		$accountLink = null;
		if ( wsh_elm_fs()->is_registered() ) {
			$accountLink = sprintf(
				'<a href="%s">%s</a>',
				esc_attr(wsh_elm_fs()->get_account_url()),
				_x('Account', 'Freemius account link', 'error-log-monitor')
			);
		}

		//Pro version call-to-action.
		if ( wsh_elm_fs()->is_not_paying() ) {
			echo 'Upgrade to Pro to get these additional features: ';
			echo '<ul class="elm-pro-features">';
			echo '<li>"Summary" tab that groups together identical errors.</li>';
			echo '<li>Stack traces for warnings and notices, not just fatal errors.</li>';
			echo '<li>Error context like page URL, current filter, and more.</li>';
			echo '</ul>';

			echo '<p>';
			printf(
				'<a href="%s" target="_blank" rel="noopener" title="Opens in a new tab">%s</a>',
				esc_attr('https://errorlogmonitor.com/'),
				_x('Details', 'a link to Pro version information', 'error-log-monitor')
			);
			echo ' | ';

			if ( wsh_elm_fs()->is_pricing_page_visible() ) {
				printf(
					'<a href="%s">%s</a>',
					esc_attr(wsh_elm_fs()->get_upgrade_url()),
					__('Upgrade to Pro', 'error-log-monitor')
				);
			}

			if ( !empty($accountLink) ) {
				echo ' | ' . $accountLink;
			}
			echo '</p>';
		} else if ( !empty($accountLink) ) {
			echo '<p>' . $accountLink . '</p>';
		}
	}

	public function ajaxHideUpgradeNotice() {
		$this->settings->set('enable_premium_notice', false);
		return array('success' => true);
	}

	/** @noinspection PhpFullyQualifiedNameUsageInspection */
	public function ajaxDownloadLog() {
		if ( !$this->userCanDownloadLog() ) {
			return new WP_Error(
				'permission_denied',
				__('You do not have permission to download the log file.', 'error-log-monitor'),
				403
			);
		}

		$log = Elm_PhpErrorLog::autodetect();
		if ( is_wp_error($log) ) {
			return $log;
		}

		//The log file may have been deleted or moved since the download link was generated.
		if ( !file_exists($log->getFileName()) ) {
			return new WP_Error(
				'file_not_found',
				__('The log file was not found. It may have been deleted or moved.', 'error-log-monitor'),
				404
			);
		}

		if ( !$this->isCompressedLogDownloadPossible() ) {
			return new WP_Error(
				'zip_stream_incompatible',
				__(
					'The server does not support the ZipStream-PHP library or an incompatible version is loaded.',
					'error-log-monitor'
				),
				501
			);
		}

		//Downloading a large log file could take a while. Let's increase the time limit.
		set_time_limit(2 * 60 * 60);

		//Close and clear all output buffers. Buffering negates the benefits of streaming,
		//and any content already buffered would probably be either superfluous output from
		//other plugins or PHP warnings/notices.
		while ((ob_get_level() > 0)) {
			ob_end_clean();
		}

		//Convert exceptions to WP_Error instances for easier handling.
		try {
			$this->setupZipStreamAutoloader();

			//Use ZipStream-PHP to compress the log file on-the-fly.
			//Most of the plugin still supports PHP 5.6, so we can't use named parameters here.
			//We have to go through all the constructor parameters to get to the archive name.
			$zip = new ZipStream\ZipStream(
				ZipStream\OperationMode::NORMAL,
				'',                                   //Comment
				null,                                 //Output stream.
				ZipStream\CompressionMethod::DEFLATE,
				6,                                    //Compression level
				true,                                 //Enable Zip64
				true,                                 //defaultEnableZeroHeader
				true,                                 //Send HTTP headers
				null,                                 //Header callback
				'php-errors.zip'                      //Archive name
			);

			$logFilePath = $log->getFileName();
			$zip->addFileFromPath(basename($logFilePath), $logFilePath);
			$zip->finish();
		} catch (\ZipStream\Exception\FileNotFoundException $e) {

			return new WP_Error('file_not_found', $e->getMessage(), 404);
		} catch (\ZipStream\Exception\FileNotReadableException $e) {
			return new WP_Error('file_not_readable', $e->getMessage(), 403);
		} catch (\ZipStream\Exception\OverflowException $e) {
			return new WP_Error('zip_overflow', $e->getMessage(), 500);
		} catch (\Exception $e) {
			return new WP_Error('general_zip_error', $e->getMessage(), 500);
		}

		exit;
	}

	public static function getInstance($settings, $plugin) {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new self($settings, $plugin);
		}
		return $instance;
	}
}