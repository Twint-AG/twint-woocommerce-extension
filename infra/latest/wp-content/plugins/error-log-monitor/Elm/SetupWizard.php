<?php

class Elm_SetupWizard {
	private $requiredCapability = 'update_core';
	/**
	 * @var Elm_Plugin
	 */
	private $plugin;

	private $ajaxHelperHandle;

	public function __construct($plugin) {
		$this->plugin = $plugin;

		add_action('admin_notices', array($this, 'displayWizardNotice'));

		$action = ajaw_v1_CreateAction('elm-start-auto-setup')
			->handler(array($this, 'ajaxAutoSetup'))
			->permissionCallback(array($this, 'userCanRunWizard'))
			->register();
		$this->ajaxHelperHandle = $action->getScriptHandle();

		ajaw_v1_CreateAction('elm-check-configuration')
			->handler(array($this, 'ajaxCheckConfiguration'))
			->permissionCallback(array($this, 'userCanRunWizard'))
			->register();

		add_action('admin_enqueue_scripts', array($this, 'registerDependencies'));
	}

	public function displayWizardNotice() {
		if ( !$this->userCanRunWizard() ) {
			return;
		}

		$log = Elm_PhpErrorLog::autodetect();
		if ( !is_wp_error($log) ) {
			//Logging is already configured so the wizard has nothing to do.
			return;
		}

		wp_enqueue_style('elm-sw-notice');
		wp_enqueue_script('elm-setup-wizard');

		echo '<div class="notice notice-info" id="elm-sw-setup-wizard-notice">';
		printf(
			'<p id="elm-sw-heading"><strong>%s</strong></p>',
			__('Error Log Monitor setup', 'error-log-monitor')
		);

		echo '<div id="elm-sw-step-1">';
		printf(
			'<p>%s</p>',
			__(
				'To start logging errors you\'ll need to make a few changes to the WordPress configuration.',
				'error-log-monitor'
			)
		);

		echo '<p>';
		submit_button(
			__('Use Recommended Settings', 'error-log-monitor'),
			'primary',
			'elm-sw-use-recommended-settings',
			false,
			array(
				'id'       => 'elm-sw-use-recommended-settings',
				'disabled' => 'disabled', //Will be enabled by JavaScript.
			)
		);
		echo ' ';

		$manualButtonLabel = _x('Manual Configuration', 'setup wizard button', 'error-log-monitor');
		submit_button(
			$manualButtonLabel,
			'secondary',
			'elm-sw-toggle-manual-instructions',
			false,
			array(
				'id'              => 'elm-sw-toggle-manual-instructions',
				'data-show-label' => $manualButtonLabel,
				'data-hide-label' => _x('Hide Manual Instructions', 'setup wizard button', 'error-log-monitor'),
				'disabled'        => 'disabled',
			)
		);
		echo '</p>';

		echo '</div>';

		printf(
			'<p id="elm-sw-step-1-progress" style="display: none;"><span class="spinner is-active elm-sw-spinner"></span> %s</p>',
			esc_html__('Creating a log file...', 'error-log-monitor')
		);

		echo '<div id="elm-sw-step-2" style="display: none;">';
		echo '<p>';
		printf(
			esc_html__('Please add this code to your %s and then click "Done":', 'error-log-monitor'),
			'<code>wp-config.php</code>'
		);
		echo '</p>';

		echo '<code id="elm-sw-config-code" class="elm-sw-multiline-code">'
			. '//Placeholder text. You should never see this.' . "\n"
			. '//...</code>';

		if ( !constant('WP_DEBUG') ) {
			echo '<p>';
			printf(
				esc_html__(
					'Recommended: To log all types of errors instead of only fatal errors and warnings, also find the line %1$s and change it to %2$s.',
					'error-log-monitor'
				),
				"<code>define(&nbsp;'WP_DEBUG', false );</code>",
				"<code>define(&nbsp;'WP_DEBUG', <strong>true</strong> );</code>"
			);
			echo '</p>';
		}

		echo '<p>';
		printf(
		/* translators: A link to English-language documentation about editing wp-config.php. */
			esc_html__('Documentation: %s', 'error-log-monitor'),
			'<a href="https://codex.wordpress.org/Editing_wp-config.php" target="_blank">Editing wp-config.php</a>'
		);
		echo '</p>';

		echo '<p>';
		submit_button(
			esc_html(_x('Done', 'setup wizard button', 'error-log-monitor')),
			'primary',
			'elm-sw-step-2-done',
			false,
			array('id' => 'elm-sw-step-2-done-button')
		);
		echo '</p>';

		echo '</div>';

		//Final step.
		printf(
			'<p id="elm-sw-step-2-progress" style="display: none;"><span class="spinner is-active elm-sw-spinner"></span> %s</p>',
			esc_html__('Checking configuration...', 'error-log-monitor')
		);

		echo '<div id="elm-sw-setup-success" style="display: none;">';
		printf(
			'<p><strong>%s</strong></p>',
			esc_html__('Setup complete!', 'error-log-monitor')
		);

		echo '<p id="elm-sw-success-comment">[This will be replaced with the message returned via AJAX.]</p>';
		echo '</div>';

		echo '<div id="elm-sw-setup-error" style="display: none;">';

		echo '<p>', esc_html_x('There was an error:', 'setup wizard error prefix', 'error-log-monitor'), '<br>';
		echo '<strong><span id="elm-sw-setup-error-message">[This will be replaced with the error returned via AJAX.]</span></strong>';
		echo '</p>';

		echo '<p>', esc_html__('Please follow the manual configuration instructions instead.'), '</p>';

		echo '</div>';

		$this->displayManualInstructions();

		echo '</div>';
	}

	private function displayManualInstructions() {
		echo '<div id="elm-sw-manual-instructions" style="display: none;">';

		printf('<h3>%s</h3>', esc_html__('Manual Configuration Instructions', 'error-log-monitor'));

		echo '<p>';
		esc_html_e('To get started you need to do two things:', 'error-log-monitor');
		echo '</p>';

		echo '<ol>';
		echo '<li>', esc_html__('Create a log file.', 'error-log-monitor'), '</li>';
		echo '<li>', esc_html__('Configure PHP to write all errors to that file.', 'error-log-monitor'), '</li>';
		echo '</ol>';

		printf('<h3>%s</h3>', esc_html__('Creating a Log File', 'error-log-monitor'));

		echo '<p>', esc_html__(
			"First, decide where you want to put the log file on your server. 
			For security purposes, it should usually be in a directory that can't be accessed by site visitors 
			and non-admin users. For example, you could create a \"php-logs\" directory above the web root and 
			place the file there.",
			'error-log-monitor'
		), '</p>';

		$suggestedLogFileNames = array('php-errors.log', 'error_log_example.com.txt', 'WordPress_errors.log');
		echo '<p>', sprintf(
			esc_html__(
				"Create an empty text file and upload it to your selected directory. The file name doesn't matter. 
				Here are some suggestions: %s",
				'error-log-monitor'
			),
			'"' . implode('", "', $suggestedLogFileNames) . '"'
		), '</p>';

		echo '<p>', sprintf(
			esc_html__(
				"Change the file permissions to allow PHP to write to the file. This step will vary depending on 
				the server configuration. If you're unsure what permissions to use, try using the same settings as
				for the files in %s, or refer to the documentation provided by your hosting provider.",
				'error-log-monitor'
			),
			'<code>/wp-content/uploads</code>'
		), '</p>';

		printf('<h3>%s</h3>', esc_html__('Changing the PHP Configuration', 'error-log-monitor'));

		echo '<p>', sprintf(
			esc_html__(
				'Next, please add this code to %1$s and replace the placeholder %2$s with the actual path to the new log file:',
				'error-log-monitor'
			),
			'<code>wp-config.php</code>',
			'<code>/path/to/log-file.txt</code>'
		), '</p>';

		echo '<code id="elm-sw-manual-config-code" class="elm-sw-multiline-code">';
		echo esc_html($this->generateConfigCode('/path/to/log-file.txt'));
		echo '</code>';

		echo '<p>';
		printf(
			esc_html__(
				'Also, find the line %1$s and change it to %2$s. This code enables "debug" mode in WordPress. 
				This is makes it possible to log everything including PHP notices and other minor errors. 
				If debugging is disabled, WordPress will automatically change error reporting settings to log 
				only fatal errors and warnings.',
				'error-log-monitor'
			),
			"<code>define(&nbsp;'WP_DEBUG', false );</code>",
			"<code>define(&nbsp;'WP_DEBUG', true );</code>"
		);

		printf(
			' <a href="https://codex.wordpress.org/WP_DEBUG">%s</a>',
			esc_html__('More information about WP_DEBUG.', 'error-log-monitor')
		);
		echo '</p>';

		echo '<p>', esc_html__(
			"When that's done, please go to the Dashboard. You should see a message like 
			\"Log is empty\" in the \"PHP Error Log\" widget. This is normal - the log will stay empty 
			until an error actually happens.",
			'error-log-monitor'
		), '</p>';

		echo '</div>';
	}

	public function ajaxAutoSetup() {
		//Access note: At this point the AJAX library has already checked the user permissions
		//by calling `userCanRunWizard` so we don't need to do that again.
		@ini_set('display_errors', 0);
		$result = $this->createErrorLog();

		if ( is_wp_error($result) ) {
			return $result;
		}
		$logFileName = $result;

		$configurationCode = $this->generateConfigCode($logFileName);

		return array(
			'success'        => true,
			'logFileName'    => $logFileName,
			'code'           => $configurationCode,
			'wpDebugEnabled' => (bool)(constant('WP_DEBUG')),
		);
	}

	private function createErrorLog() {
		//It would be more secure to place the log outside the document root, but PHP might not have
		//write access to that directory. Also, users would likely find it very surprising if a plugin
		//modified files or directories outside the WordPress directory. That's why we'll put the log
		//directory in `wp-content` instead.
		$possibleParentDirectories = array(
			WP_CONTENT_DIR,
		);

		$logDirectory = null;
		foreach ($possibleParentDirectories as $parentDirectory) {
			$dir = wp_normalize_path($parentDirectory . DIRECTORY_SEPARATOR . 'elm-error-logs');
			if ( is_dir($dir) ) {
				//It looks like the user has run the wizard before. We can reuse the directory.
				$logDirectory = $dir;
				break;
			} else {
				if ( @mkdir($dir, 0770) ) {
					$logDirectory = $dir;
					break;
				}
			}
		}

		if ( $logDirectory === null ) {
			return new WP_Error(
				'no_writable_directory',
				__('Could not find a suitable location for the log file.', 'error-log-monitor')
			);
		}

		//Create a log file in the selected directory.
		$logFileName = wp_normalize_path($logDirectory . DIRECTORY_SEPARATOR . 'php-errors.log');
		if ( !file_exists($logFileName) ) {
			if ( @file_put_contents($logFileName, '') === false ) {
				return new WP_Error(
					'file_creation_failed',
					sprintf(
						__('Could not create a log file in directory <code>%s</code>.', 'error-log-monitor'),
						esc_html($logDirectory)
					)
				);
			}
			chmod($logFileName, 0660);
		} else if ( !is_writable($logFileName) ) {
			//It's OK if the file already exists, but it has to be writable.
			return new WP_Error(
				'inaccessible_file_exists',
				__('The log file already exists but it is not writable: <code>%s</code>.', 'error-log-monitor')
			);
		}

		//Copy files from the template to the log directory.
		$filesToCopy = array('index.php', '.htaccess');
		$templateDirectory = realpath(dirname(__FILE__) . '/../log-dir-template');
		if ( is_dir($templateDirectory) ) {
			foreach ($filesToCopy as $fileName) {
				$sourceFile = $templateDirectory . DIRECTORY_SEPARATOR . $fileName;
				if ( is_file($sourceFile) ) {
					copy(
						$templateDirectory . DIRECTORY_SEPARATOR . $fileName,
						$logDirectory . DIRECTORY_SEPARATOR . $fileName
					);
				}
			}
		}

		return $logFileName;
	}

	private function generateConfigCode($logFileName) {
		$configLines = array(
			"//Enable error logging.",
			"@ini_set('log_errors', 'On');",
			sprintf("@ini_set('error_log', %s);", var_export($logFileName, true)),
		);

		if ( WP_DEBUG_DISPLAY || !WP_DEBUG || $this->isTruthyIniValue(ini_get('display_errors')) ) {
			$configLines = array_merge(
				$configLines,
				array(
					"", //A blank line for better readability.
					"//Don't show errors to site visitors.",
					"@ini_set('display_errors', 'Off');",
					"if ( !defined('WP_DEBUG_DISPLAY') ) {",
					"\tdefine('WP_DEBUG_DISPLAY', false);",
					"}",
				)
			);
		}

		$configurationCode = implode("\n", $configLines);
		return $configurationCode;
	}

	private function isTruthyIniValue($value) {
		if ( empty($value) ) {
			return false;
		}
		return in_array(strtolower(strval($value)), array('1', 'on', 'true'));
	}

	public function ajaxCheckConfiguration() {
		$result = Elm_PhpErrorLog::autodetect();
		if ( is_wp_error($result) ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => __(
				'The log file will be empty at first. This is normal. When any PHP errors happen, they should show up in the log.',
				'error-log-monitor'
			),
		);
	}

	public function userCanRunWizard() {
		return current_user_can($this->requiredCapability) && is_super_admin();
	}

	public function registerDependencies() {
		wp_register_style(
			'elm-sw-notice',
			plugins_url('css/setup.css', $this->plugin->getPluginFile()),
			array(),
			'20180831'
		);

		wp_register_script(
			'elm-setup-wizard',
			plugins_url('js/setup.js', $this->plugin->getPluginFile()),
			array('jquery', $this->ajaxHelperHandle),
			'20180831',
			true
		);
	}
}