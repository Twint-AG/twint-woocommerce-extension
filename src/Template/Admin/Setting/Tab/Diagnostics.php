<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting\Tab;

use Twint\Woo\Command\CliCommand;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Plugin;
use Twint\Woo\Template\Admin\Setting\TabItem;

class Diagnostics extends TabItem
{
    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Diagnostics', 'twint-woocommerce-extension');
    }

    public static function fields(): array
    {
        return [];
    }

    public static function getContents(array $data = []): string
    {
        $info = self::getInformation();

        $trigger = Plugin::di('cli.trigger', false);
        $trigger->handle();

        ob_start();
        require Plugin::abspath() . 'src/View/Admin/diagnostics.php';

        return ob_get_clean();
    }

    public static function getInformation(): array
    {
        $info = [];

        [$cliVersion, $isExecutable, $cliInfo, $shellExecAllowed] = self::getCliInformation();

        $info[] = [
            'label' => 'Plugin version:',
            'value' => TwintConstant::PLUGIN_VERSION,
            'valid' => true,
        ];

        $info[] = [
            'label' => 'WooCommerce version:',
            'value' => defined('WC_VERSION') ? WC_VERSION : 'Unknown',
            'valid' => true,
        ];

        $info[] = [
            'label' => 'Wordpress version:',
            'value' => get_bloginfo('version'),
            'valid' => true,
        ];

        $info[] = [
            'label' => 'Plugin install source:',
            'value' => TwintConstant::INSTALL_SOURCE,
            'valid' => true,
        ];

        $info[] = [
            'label' => 'PHP version (8.1 or earlier):',
            'value' => PHP_VERSION,
            'valid' => version_compare(PHP_VERSION, '8.1.0', '>'),
        ];

        $soapAvailable = class_exists('SoapClient');
        $info[] = [
            'label' => __('SOAP requirement:', 'twint-woocommerce-extension'),
            'value' => $soapAvailable ? 'Yes' : 'No',
            'valid' => $soapAvailable,
        ];

        $info[] = [
            'label' => __('PHP CLI version (8.1 or earlier):', 'twint-woocommerce-extension'),
            'value' => $cliVersion,
            'valid' => version_compare($cliVersion, '8.1.0', '>'),
        ];

        $info[] = [
            'label' => __('PHP Executable path:', 'twint-woocommerce-extension'),
            'value' => apply_filters('twint_poll_php_executable', Plugin::php()),
            'valid' => true,
        ];

        $info[] = [
            'label' => __('Function `shell_exec` is allowed: ', 'twint-woocommerce-extension'),
            'value' => $shellExecAllowed ? 'Yes' : 'No',
            'valid' => (bool) $shellExecAllowed,
        ];

        $info[] = [
            'label' => __('TWINT command is executable: ', 'twint-woocommerce-extension'),
            'value' => $isExecutable ? 'Yes' : 'No',
            'valid' => (bool) $isExecutable,
        ];

        $info[] = [
            'label' => __('TWINT PHP CLI Command Execution Test: ', 'twint-woocommerce-extension'),
            'value' => $cliInfo,
            'valid' => (bool) $cliInfo,
        ];


        $wpCli = function_exists('shell_exec') && shell_exec('wp --info') !== null;
        $info[] = [
            'label' => 'WP-CLI is available:',
            'value' => $wpCli ? 'Yes' : 'No',
            'valid' => true,
        ];

        $cliSupport = get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes';
        $info[] = [
            'label' => 'PHP CLI flag:',
            'value' => $cliSupport ? 'Yes' : 'No',
            'valid' => $cliSupport,
        ];

        $validated = get_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG) === TwintConstant::YES;
        $info[] = [
            'label' => 'Credentials is validated:',
            'value' => $validated ? 'Yes' : 'No',
            'valid' => $validated,
        ];

        $testMode = get_option(TwintConstant::TEST_MODE) === TwintConstant::YES;
        $info[] = [
            'label' => 'Credentials with test mode:',
            'value' => $testMode ? 'Yes' : 'No',
            'valid' => $testMode,
        ];

        $pluginsMerged = self::getPluginsWithActiveCheck();
        $info[] = [
            'label' => __('Plugins:', 'twint-woocommerce-extension'),
            'value' => $pluginsMerged === [] ? 'None' : implode(', ', $pluginsMerged),
            'valid' => true,
        ];

        return $info;
    }

    public static function getCliInformation(): array
    {
        $cliVersion = 'Unknown';
        $filePath = Plugin::abspath() . 'bin/console';
        $php = apply_filters('twint_poll_php_executable', Plugin::php());

        // Get PHP CLI version safely
        if (function_exists('shell_exec')) {
            $output = @shell_exec(escapeshellarg($php) . ' -r "echo PHP_VERSION;"');
            if ($output && trim($output) !== '') {
                $cliVersion = trim($output);
            }
        }

        // Check if bin/console is executable
        $isExecutable = function_exists('is_readable') && file_exists($filePath) && @is_readable($filePath);

        // Execute CLI command safely
        $cliInfo = 'Unknown';
        $shellExecAllowed = false;
        if (function_exists('shell_exec')) {
            $shellExecAllowed = true;
            $command = escapeshellarg($php) . ' ' . escapeshellarg($filePath) . ' ' . escapeshellarg(
                CliCommand::COMMAND
            );
            $output = @shell_exec($command);
            if ($output && trim($output) !== '') {
                $cliInfo = trim($output);
            }
        }

        return [$cliVersion, $isExecutable, $cliInfo, $shellExecAllowed];
    }

    public static function allowSaveChanges(): bool
    {
        return false;
    }

    /**
     * Merge installed plugins into a single list and append a checkmark for enabled ones.
     *
     * Example label: "Plugin Name (vX.Y.Z) by Author (✓)" when enabled.
     *
     * @return array<int,string>
     */
    public static function getPluginsWithActiveCheck(): array
    {
        $installed = self::buildInstalledPluginsMap();

        // Collect active plugins (site level)
        $active = [];
        $activeOption = get_option('active_plugins', []);
        if (is_array($activeOption)) {
            $active = $activeOption;
        }

        // Include network-activated plugins (multisite)
        if (function_exists('is_multisite') && is_multisite()) {
            $network = get_site_option('active_sitewide_plugins', []);
            if (is_array($network)) {
                $active = array_merge($active, array_keys($network));
            }
        }

        // Turn active list into a set for quick lookup
        $activeSet = array_fill_keys($active, true);

        $result = [];
        foreach ($installed as $path => $label) {
            $result[] = isset($activeSet[$path]) ? ($label . ' (✓)') : $label;
        }

        return $result;
    }

    /**
     * Build a map of installed plugins keyed by their file path with enriched labels.
     *
     * @return array<string,string> [pluginFile => "Name (vX.Y.Z) by Author"]
     */
    private static function buildInstalledPluginsMap(): array
    {
        $installed = [];

        if (function_exists('get_plugins')) {
            $plugins = get_plugins();
            if (is_array($plugins)) {
                foreach ($plugins as $path => $data) {
                    $name = $data['Name'] ?? $path;
                    $version = $data['Version'] ?? '';
                    $author = $data['Author'] ?? '';

                    $labelParts = array_filter([
                        $name,
                        $version ? "(v{$version})" : '',
                        $author ? "by {$author}" : '',
                    ]);

                    $installed[$path] = implode(' ', $labelParts);
                }
            }
        }

        return $installed;
    }
}
