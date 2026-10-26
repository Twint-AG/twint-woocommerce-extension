<?php

declare(strict_types=1);

namespace Twint\Woo;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Symfony\Component\Process\PhpExecutableFinder;
use Twint\Woo\Command\WpCliPollCommand;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\ContainerFactory;
use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Method\ExpressCheckout;
use Twint\Woo\Model\Method\RegularCheckout;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Setup\Installer;
use WC_Payment_Gateway;
use WP_CLI;

class Plugin
{
    public static string $pluginFile;

    /**
     * Plugin bootstrapping.
     */
    public static function init(string $path): void
    {
        self::$pluginFile = $path;

        self::registerCLI();

        // Twint Payments gateway class.
        add_action('plugins_loaded', [self::class, 'loaded'], 0);

        // Make the Twint Payment gateway available to WC.
        add_filter('woocommerce_payment_gateways', [self::class, 'addPaymentGateways']);

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', [self::class, 'registerCheckoutBlocks']);

        register_activation_hook(self::pluginFile(), [TwintIntegration::class, 'install']);
        register_deactivation_hook(self::pluginFile(), [TwintIntegration::class, 'uninstall']);

        add_action('init', [self::class, 'createCustomWooCommerceStatus']);
        add_filter('wc_order_statuses', [self::class, 'addCustomWooCommerceStatusToList']);

        // Declare compatibility with WooCommerce HPOS
        add_action('before_woocommerce_init', static function () {
            if (class_exists(OrderUtil::class)) {
                FeaturesUtil::declare_compatibility('custom_order_tables', (new Plugin())->pluginFile());
            }
        });

        add_action('admin_post_twint_download_diagnostics', [self::class, 'twint_download_diagnostics_handler']);
        add_action(
            'admin_post_twint_download_order_diagnostics',
            [self::class, 'twint_download_order_diagnostics_handler']
        );
    }

    public static function twint_download_diagnostics_handler()
    {
        self::di('diagnostic.service')->downloadGlobalDiagnostics();
    }

    public static function twint_download_order_diagnostics_handler()
    {
        self::di('diagnostic.service')->downloadOrderDiagnostics();
    }

    public static function registerCLI(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('twint-poll', WpCliPollCommand::class);
        }
    }

    public static function createCustomWooCommerceStatus(): void
    {
        register_post_status(
            RegularCheckoutGateway::getOrderStatusAfterFirstTimeCreatedOrder(),
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
                'label' => __('Pending payment', 'woocommerce'),
                'public' => true,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
            ]
        );
    }

    public static function addCustomWooCommerceStatusToList($orderStatuses): array
    {
        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
        $orderStatuses[RegularCheckoutGateway::getOrderStatusAfterFirstTimeCreatedOrder()] = __('Pending payment', 'woocommerce');

        return $orderStatuses;
    }

    /**
     * Add the Twint Payment gateway to the list of available gateways.
     */
    public static function addPaymentGateways(array $gateways): array
    {
        $currency = get_option('woocommerce_currency');
        if ($currency !== TwintConstant::SUPPORTED_CURRENCY) {
            return $gateways;
        }

        /**
         * Insert TWINT payment methods into the woo payment methods
         */
        $gateways[] = RegularCheckoutGateway::class;
        $gateways[] = ExpressCheckoutGateway::class;

        return $gateways;
    }

    /**
     * Plugin loaded.
     */
    public static function loaded(): void
    {
        // Check for active plugins.
        if (!self::isPluginActivated('woocommerce/woocommerce.php') || !class_exists(WC_Payment_Gateway::class)) {
            return;
        }

        self::maybeUpgradeSchema();

        self::registerAdminNotices();

        self::loadTranslations();

        $instance = self::di('twint.integration', true);
        add_filter(
            'plugin_action_links_' . plugin_basename(self::pluginFile()),
            [$instance, 'adminPluginSettingsLink']
        );

        self::di('express.button', true);
        self::di('monitor.cron', true);

        // Self-healing: re-schedule cron if event was lost (DB migration, clone, etc.)
        MonitorPairingCronJob::scheduleCronjob();
    }

    public static function isPluginActivated(string $plugin): bool
    {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Test to see if WooCommerce is active (including network activated).
        $pluginPath = trailingslashit(WP_PLUGIN_DIR) . $plugin;

        $active = wp_get_active_and_valid_plugins();
        if (is_multisite() && function_exists('wp_get_active_network_plugins')) {
            $active = array_merge($active, wp_get_active_network_plugins());
        }

        return in_array($pluginPath, $active, true);
    }

    public static function di(string $container, bool $lazyLoad = true): mixed
    {
        return ContainerFactory::instance()->get($container, $lazyLoad);
    }

    /**
     * Registers WooCommerce Blocks integration.
     */
    public static function registerCheckoutBlocks(): void
    {
        if (class_exists(PaymentMethodRegistry::class)) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                static function (PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new ExpressCheckout());
                    $payment_method_registry->register(new RegularCheckout());
                }
            );
        }
    }

    /**
     * Utility function for enqueue JS files with dependencies and version
     * Only support for script files in /dist folder
     * @param null|mixed $callback
     */
    public static function enqueueScript(string $id, string $path, bool $useHook = true, $callback = null): void
    {
        $func = function () use ($id, $path, $callback) {
            $name = "twint-woocommerce-extension-{$id}";
            $asset = require self::abspath() . 'dist' . str_replace('.js', '.asset.php', $path);

            wp_enqueue_script($name, Plugin::dist($path), $asset['dependencies'], $asset['version'], false);

            if (is_callable($callback)) {
                $callback();
            }
        };

        // Hook into wp_enqueue_scripts or another relevant hook
        $useHook ? add_action('wp_enqueue_scripts', $func) : $func();
    }

    /**
     * Plugin url.
     */
    public static function abspath(): string
    {
        return trailingslashit(plugin_dir_path(self::pluginFile()));
    }

    public static function dist(string $fileName): ?string
    {
        // Ensure the asset path starts with a slash
        $fileName = ltrim($fileName, '/');

        // Define the local path to the assets directory
        $localPath = rtrim(self::pluginUrl(), '/') . '/dist/';

        // Return the full asset path
        return $localPath . $fileName;
    }

    public static function pluginUrl(): string
    {
        return untrailingslashit(plugins_url('/', self::pluginFile()));
    }

    public static function assets(string $asset): ?string
    {
        // Ensure the asset path starts with a slash
        $asset = ltrim($asset, '/');

        // Define the local path to the assets directory
        $localPath = rtrim(self::pluginUrl(), '/') . '/assets/';

        // Return the full asset path
        return $localPath . $asset;
    }

    public static function php(): string
    {
        static $php;
        if ($php === null) {
            $php = (new PhpExecutableFinder())->find() ?: 'php';
        }

        return $php;
    }

    protected static function pluginFile(): string
    {
        return self::$pluginFile;
    }

    private static function loadTranslations(): void
    {
        // Compatible for old WP versions
        $locale = determine_locale();
        $locale = apply_filters('plugin_locale', $locale, 'woocommerce');
        load_textdomain(
            'twint-woocommerce-extension',
            plugin_dir_path(self::pluginFile()) . 'languages/twint-woocommerce-extension-' . $locale . '.mo'
        );

        // from WP 6.5 only need this
        load_plugin_textdomain('twint-woocommerce-extension', false, plugin_dir_path(self::pluginFile()) . 'languages');
    }

    private static function maybeUpgradeSchema(): void
    {
        if ((int) get_option('twint_db_version', 0) >= TwintConstant::TWINT_DB_VERSION) {
            return;
        }
        if (get_transient('twint_migration_retry_after')) {
            return;
        }

        try {
            /** @var PairingRepository $repo */
            $repo = self::di('pairing.repository', false);
            $lock = PairingRepository::scopedLockName('migrate');
            if (!$repo->acquireLock($lock)) {
                return; // another request is migrating
            }

            try {
                // @phpstan-ignore greaterOrEqual.alwaysFalse
                if ((int) get_option('twint_db_version', 0) >= TwintConstant::TWINT_DB_VERSION) {
                    return; // re-check after acquiring lock
                }

                /** @var Installer $installer */
                $installer = self::di('installer', false);
                $installer->upgrade();

                global $wpdb;

                // Set upgrade timestamp once, regardless of ALTER outcome.
                $now = $wpdb->get_var('SELECT NOW()');
                add_option('twint_upgraded_at', $now);

                if ($installer->columnsReady()) {
                    update_option('twint_db_version', TwintConstant::TWINT_DB_VERSION);
                    delete_option('twint_migration_failed');
                    delete_transient('twint_migration_retry_after');
                } else {
                    set_transient('twint_migration_retry_after', '1', HOUR_IN_SECONDS);
                    update_option('twint_migration_failed', true);
                }
            } finally {
                $repo->releaseLock($lock);
            }
        } catch (\Throwable $e) {
            error_log('TWINT migration error: ' . $e->getMessage());
            set_transient('twint_migration_retry_after', '1', HOUR_IN_SECONDS);
            update_option('twint_migration_failed', true);
        }
    }

    private static function registerAdminNotices(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_notices', static function () {
            if (!current_user_can('manage_woocommerce')) {
                return;
            }

            // Trigger DEFINER error (1449)
            if (get_option('twint_trigger_definer_error', false)) {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__(
                    'TWINT: A database trigger is causing errors. Please ask your hosting provider to run:',
                    'twint-woocommerce-extension'
                );
                echo ' <code>DROP TRIGGER IF EXISTS before_update_twint_pairing</code>';
                echo '</p></div>';
            }

            // Migration failed
            if (get_option('twint_migration_failed', false)) {
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__(
                    'TWINT: Database migration could not complete. The plugin is running without optimistic locking. Please check that your database user has ALTER TABLE privilege.',
                    'twint-woocommerce-extension'
                );
                echo '</p></div>';
            }

            // Guard: schema-dependent queries only when columns exist
            /** @var PairingRepository $repo */
            $repo = Plugin::di('pairing.repository', false);
            if ($repo->isSchemaReady()) {
                global $wpdb;
                $table = PairingRepository::tableName();

                // Orders needing review (TWINT captured but unexpected status)
                $reviewCount = get_transient('twint_needs_review_count');
                if ($reviewCount === false) {
                    $reviewCount = (int) $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$table}
                         WHERE order_settled = " . TwintConstant::ORDER_SETTLED_NEEDS_REVIEW . '
                           AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)'
                    );
                    set_transient('twint_needs_review_count', $reviewCount, HOUR_IN_SECONDS);
                }
                if ($reviewCount > 0) {
                    echo '<div class="notice notice-error"><p>';
                    echo esc_html(sprintf(
                        /* translators: %d = number of orders needing review */
                        _n(
                            'TWINT: %d payment in the last 7 days was captured by TWINT but the WooCommerce order is in an unexpected status. Please review.',
                            'TWINT: %d payments in the last 7 days were captured by TWINT but the WooCommerce order is in an unexpected status. Please review.',
                            $reviewCount,
                            'twint-woocommerce-extension'
                        ),
                        $reviewCount
                    ));
                    echo '</p></div>';
                }

                // Reopened cancelled orders (TWINT captured, order was cancelled without refund)
                $reopenedCount = get_transient('twint_reopened_count');
                if ($reopenedCount === false) {
                    $reopenedCount = (int) $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$table}
                         WHERE order_settled = " . TwintConstant::ORDER_SETTLED_REOPENED . '
                           AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)'
                    );
                    set_transient('twint_reopened_count', $reopenedCount, HOUR_IN_SECONDS);
                }
                if ($reopenedCount > 0) {
                    echo '<div class="notice notice-warning"><p>';
                    echo esc_html(sprintf(
                        /* translators: %d = number of reopened orders */
                        _n(
                            'TWINT: %d cancelled order was reopened in the last 7 days because TWINT captured the payment. Please check stock and fulfillment.',
                            'TWINT: %d cancelled orders were reopened in the last 7 days because TWINT captured the payment. Please check stock and fulfillment.',
                            $reopenedCount,
                            'twint-woocommerce-extension'
                        ),
                        $reopenedCount
                    ));
                    echo '</p></div>';
                }
            }
        });
    }
}
