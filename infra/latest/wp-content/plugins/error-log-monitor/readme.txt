=== Error Log Monitor ===
Contributors: whiteshadow
Tags: dashboard widget, administration, error reporting, admin, php
Requires at least: 4.5
Tested up to: 6.7
Stable tag: 1.7.10

Adds a Dashboard widget that displays the latest messages from your PHP error log. It can also send logged errors to email.

== Description ==

This plugin adds a Dashboard widget that displays the latest messages from your PHP error log. It can also send you email notifications about newly logged errors.

**Features**

* Automatically detects error log location.
* Explains how to configure PHP error logging if it's not enabled yet.
* The number of displayed log entries is configurable.
* Sends you email notifications about logged errors (optional).
* Configurable email address and frequency.
* You can easily clear the log file.
* The dashboard widget is only visible to administrators.
* Optimized to work well even with very large log files.

**Usage**

Once you've installed the plugin, go to the Dashboard and enable the "PHP Error Log" widget through the "Screen Options" panel. The widget should automatically display the last 20 lines from your PHP error log. If you see an error message like "Error logging is disabled" instead, follow the displayed instructions to configure error logging.

Email notifications are disabled by default. To enable them, click the "Configure" link in the top-right corner of the widget and enter your email address in the "Periodically email logged errors to:" box. If desired, you can also change email frequency by selecting the minimum time interval between emails from the "How often to send email" drop-down.

== Installation ==

Follow these steps to install the plugin on your site: 

1. Download the .zip file to your computer.
2. Go to *Plugins -> Add New* and select the "Upload" option.
3. Upload the .zip file.
4. Activate the plugin through the *Plugins -> Installed Plugins" page.
5. Go to the Dashboard and enable the "PHP Error Log" widget through the "Screen Options" panel.
6. (Optional) Click the "Configure" link in the top-right of the widget to configure the plugin.

== Screenshots ==

1. The "PHP Error Log" widget added by the plugin. 
2. Dashboard widget configuration screen.

== Changelog ==

= 1.7.10 =
* Fix likely crash due to some Freemius SDK files being missing.

= 1.7.9 =
* Made the "Stack Trace" section collapsible. By default, it will always be expanded if available. You can change that in the widget configuration, in the new "Context" section. The plugin will also remember the state of recently collapsed/expanded "Stack Trace" sections (per unique log message). 
* Updated the Freemius SDK to version 2.9.0.
* Tested up to WP 6.6.2.

= 1.7.8 =
* Added a "Download Log" button. It downloads the PHP error log as a ZIP file. The button will only appear if the log is not empty and the server meets a number of additional requirements (PHP 8.1+, 64-bit PHP version, zlib and mbstring extensions).
* Increased the minimum required PHP version to 7.4.
* Updated the Freemius SDK to the latest version.
* Tested up to WP 6.6-alpha.

= 1.7.7 =
* Updated the Freemius SDK to the latest version.
* Tested with WP 6.3-beta.

= 1.7.6 =
* Updated the Freemius SDK to version 2.5.8.
* Tested with WP 6.2.2 and 6.3-alpha.

= 1.7.5 =
* Fixed a visual bug where the log size limit field was too narrow in the most recent WP version.
* Tested with WP 6.2.

= 1.7.4 =
* Updated the Freemius SDK to version 2.5.3 in the hopes of fixing a couple of PHP 8.1 deprecation notices that appear to be triggered by the SDK.

= 1.7.3 =
* Fixed a number of PHP 8 deprecation warnings and compatibility issues.
* Tested with WP 6.1.

= 1.7.2 =
* Added an "Ignored regular expressions" setting. Enter one or more regex patterns in the box and the plugin will hide log entries that match any of those patterns.

= 1.7.1 =
* Added a "Clear Fixed Messages" button.
* Fixed a scheduling bug where, in certain configurations, the plugin would send some email notifications too late.
* Fixed a security issue.
* Tested with WP 5.9.1 and 6.0-alpha (briefly).

= 1.7 =
* Added a "mark as fixed" option. Like the "ignore" option, "mark as fixed" hides all existing copies of a specific error. However, if the same error happens again in the future, the plugin will make it visible again.
* Added a "Clear Ignored Messages" button. It un-ignores all previously ignored messages.
* Fixed a couple of PHP 8 deprecation warnings about a required parameter following an optional parameter.
* Tested with WP 5.6.1 and 5.7-beta.

= 1.6.13 = 
* Fixed "Deprecated: contextual_help is deprecated since version 3.3.0". While this plugin doesn't use the "contextual_help" filter, it includes a copy of scbFramework that can also be used by other active plugins. Some of those plugins could run code in scbFramework that used "contextual_help". This deprecated code has now been removed.
* Tested with WP 5.5.3.

= 1.6.12 =
* Fixed recoverable fatal errors being incorrectly presented as an unknown error type.
* Added text domain to a UI message that was missing it.
* Updated the Freemius SDK to version 2.4.1.
* Tested with WP 5.5.1 and 5.6-beta.

= 1.6.11 =
* Changed the minimum required PHP version to 5.6.
* Tested up to WP 5.5.

= 1.6.10 =
* Fixed a bug where the plugin could freeze or crash while trying to parse extremely long log entries (e.g. more than a million characters long).
* Updated the Freemius SDK to version 2.3.2.
* Tested up to WP 5.4.1.

= 1.6.9 =
* Fixed the erorr "call to undefined function get_blog_list()" when trying to access the network admin on a non-Multisite site.

= 1.6.8 =
* Fixed a conflict with WP-PageNavi, again. The fix included in the previous version was not fully effective.

= 1.6.7 =
* Fixed a conflict with WP-PageNavi that could cause a fatal error.
* Updated Freemius SDK to the latest version, which may fix some Freemius-related issues.
* Tested up to WP 5.3.

= 1.6.6 =
* Improved the way the plugin displays truncated stack traces. Now it should no longer display the last entry as a very tall and narrow block of text.
* Added a workaround for conflicts with plugins that use old versions of scbFramework.
* Tested up to WP 5.2.

= 1.6.5 =
* Fixed a bug where it wasn't possible to filter out log entries that didn't match any of the standard severity levels (notice, warning, error, etc). Now you can hide uncategorized log entries by unchecking the "Other" option in filter settings.
* Fixed a security issue.
* Tested with the final WP 5.1 release.

= 1.6.4 =
* Changed plugin configuration permissions. Now you need to have the "install_plugins" capability to change the configuration. Previous versions used the "update_core" capability.
* Fixed a bug where users who couldn't change plugin configuration were still shown a useless "Submit" button.
* Tested with WP 5.1-alpha.

= 1.6.3 =
* Added a workaround for a conflict with "Go Fetch Jobs (for WP Job Manager)" 1.4.6.
* Tested with the final WP 5.0 release.

= 1.6.2 =
* Added a setup wizard that helps new users create a log file and enable error logging. You can still do it manually you prefer. The setup notice will automatically disappear if logging is already configured.
* Fixed a bug where activating the plugin on individual sites in a Multisite network could, in some cases, trigger a fatal error.
* Additional testing with WP 5.0-alpha.

= 1.6.1 =
* Fixed the "upgrade" link being broken in certain configurations.

= 1.6 =
* Added a colored dot showing the severity level to each error message. Fatal errors are red, warnings are orange, notices and strict-standards messages are grey, and custom or unrecognized messages are blue.
* Added a new setting for email notifications: "how often to check the log for new messages". 
* Added a notice explaining how to configure WordPress to log all types of errors (including PHP notices) instead of just fatal errors and warnings.
* Added Freemius integration.
* Added a link to the Pro version to bottom of the widget.
* Improved parsing of multi-line log entries. Now the plugin will show all of the lines as part of the same message instead of treating every line as an entirely separate error.
* Improved stack trace formatting.
* In Multisite, the dashboard widget now also shows up in the network admin dashboard.
* Changed permissions so that only Super Admins can change plugin settings or clear the log file. Regular administrators can still see the widget.

= 1.5.7 =
* The widget now displays log timestamps in local time instead of UTC.
* Fixed a runtime exception "Backtrack buffer overflow" that was thrown when trying to parse very long log entries.

= 1.5.6 =
* The dashboard widget now shows the log file size and the "Clear Log" button even when all entries are filtered out.
* Tested with WP 4.9 and WP 5.0-alpha.

= 1.5.5 =
* Fixed two PHP notices: "Undefined index: schedule in [...]Cron.php on line 69" and "Undefined index: time in [...]Cron.php on line 76".
* Added "error_reporting(E_ALL)" to the example code to log all errors and notices.
* Tested up to WP 4.9-beta2.

= 1.5.4 =
* Fixed the error "can't use method return value in write context". It was a compatibility issue that only affected PHP versions below 5.5.

= 1.5.3 =
* You can send email notifications to multiple addresses. Just enter a comma-separated list of emails.
* Made sure that email notifications are sent no more often than the configured frequency even when WordPress is unreliable and triggers cron events too frequently.
* Tested up to WP 4.9-alpha-40871.

= 1.5.2 =
* Fixed a fatal error caused by a missing directory. Apparently, SVN externals don't work properly in the wordpress.org plugin repository.

= 1.5.1 =
* Added an option to ignore specific error messages. Ignored messages don't show up in the dashboard widget and don't generate email notifications, but they stay in the log file.
* Added limited support for parsing stack traces generated by PHP 7.
* Made the log output more compact.
* Improved log parsing performance.
* Fixed an "invalid argument supplied for foreach" warning in scbCron.

= 1.5 =
* Added a severity filter. For example, you could use this feature to make the plugin send notifications about fatal errors but not warnings or notices.
* Added limited support for XDebug stack traces. The stack trace will show up as part of the error message instead of as a bunch of separate entries. Also, stack trace items no longer count towards the line limit.

= 1.4.2 =
* Hotfix for a parse error that was introduced in version 1.4.1.

= 1.4.1 =
* Fixed a PHP compatibility issue that caused a parse error in Plugin.php on sites using an old version of PHP.

= 1.4 =
* Added an option to send an email notification when the log file size exceeds the specified threshold.
* Fixed a minor translation bug.
* The widget now shows the full path of the WP root directory along with setup instructions. This should make it easier to figure out the absolute path of the log file.
* Tested with WP 4.6-beta3.

= 1.3.3 =
* Added i18n support.
* Added an `elm_show_dashboard_widget` filter that lets other plugins show or hide the error log widget.
* Tested with WP 4.5.1 and WP 4.6-alpha.

= 1.3.2 =
* Tested up to WP 4.5 (release candidate).

= 1.3.1 =
* Added support for Windows and Mac style line endings.

= 1.3 =
* Added an option to display log entries in reverse order (newest to oldest).
* Added a different error message for the case when the log file exists but is not accessible.
* Only load the plugin in the admin panel and when running cron jobs.
* Fixed the error log sometimes extending outside the widget.
* Tested up to WP 4.4 (alpha version).

= 1.2.4 =
* Tested up to WP 4.2 (final release).
* Added file-based exclusive locking to prevent the plugin occasionally sending duplicate email notifications.

= 1.2.3 =
* Tested up to WP 4.2-alpha.
* Refreshing the page after clearing the log will no longer make the plugin to clear the log again.

= 1.2.2 = 
* Updated Scb Framework to the latest revision.
* Tested up to WordPress 4.0 beta.