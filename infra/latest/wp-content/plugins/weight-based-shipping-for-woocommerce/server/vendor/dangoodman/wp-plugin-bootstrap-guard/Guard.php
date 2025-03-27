<?php
namespace WbsVendors\Dgm\WpPluginBootstrapGuard;


/** @noinspection PhpUnused */
class Guard
{
    /**
     * @param string $pluginFile
     * @param string $bootstrap
     * @param list<string> $phpExt
     * @return void
     * @noinspection PhpUnused
     */
    public static function checkPrerequisitesAndBootstrap(
        $pluginFile,
        $bootstrap,
        array $phpExt = array()
    )
    {
        $instance = new self($pluginFile, $bootstrap, $phpExt);
        $instance->checkAndBoot();
    }

    private $plugin;
    private $php;
    private $wp;
    private $wc;
    private $bootstrap;
    private $phpExt;

    /**
     * @param string $pluginFile
     * @param string $bootstrap
     * @param list<string> $phpExt
     */
    private function __construct(
        $pluginFile,
        $bootstrap,
        array $phpExt = array()
    )
    {
        $meta = get_file_data($pluginFile, array(
            'name' => 'Plugin Name',
            'php_min' => 'Requires PHP',
            'wp_min' => 'Requires at least',
            'wc_min' => 'WC requires at least',
        ));

        $this->plugin = empty($meta['name']) ? 'Unnamed plugin' : $meta['name'];
        $this->php = empty($meta['php_min']) ? '5.3' : $meta['php_min'];
        $this->wp = empty($meta['wp_min']) ? '0' : $meta['wp_min'];
        $this->wc = empty($meta['wc_min']) ? '0' : $meta['wc_min'];

        $this->bootstrap = $bootstrap;
        $this->phpExt = $phpExt;
    }

    /**
     * @return list{list<string>, list<string>}
     *
     * @noinspection PhpVariableIsUsedOnlyInClosureInspection
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection used in tests
     */
    private function checkAndBoot()
    {
        $errors = array();

        $phpv = PHP_VERSION;

        if (version_compare($phpv, $this->php) < 0) {
            $errors[] =
                "You are running an outdated PHP version $phpv. 
                 The plugin requires PHP $this->php+. 
                 Contact your hosting support to switch to a newer PHP version.";
        }

        foreach ($this->phpExt as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] =
                    "PHP extension '$ext' is not loaded at the moment. 
                    Please revisit your PHP settings to enable it.";
            }
        }

        global $wp_version;
        if (isset($wp_version) && version_compare($wp_version, $this->wp) < 0) {
            $errors[] =
                "You are running an outdated WordPress version $wp_version.
                 The plugin requires $this->wp+.
                 Please update to a modern WordPress version.";
        }

        if (isset($this->wc)) {
            $error = $this->checkWcVersion($this->wc, $this->plugin);
            if ($error !== '') {
                $errors[] = $error;
            }
        }

        if (!$errors) {
            include($this->bootstrap);
        }

        $warnings = array();
        {
            require_once(__DIR__.'/Notices.php');

            if (!Notices::isDismissed($noticeId = 'dgm-zend-guard-loader')) {
                if (version_compare($phpv, $minphpv = '5.4', '<') && self::isZendGuardLoaderActive()) {
                    $warnings[$noticeId] =
                        "You are running PHP version $phpv with Zend Guard Loader extension active.
                        This server configuration might not be compatible with $this->plugin.
                        If you are getting 500 Internal Server Error or 503 Service Unavailable 
                        errors on Cart or Checkout pages when the plugin is active, disable
                        Zend Guard Loader or update your PHP version to $minphpv+.";
                }
            }

            if ($warnings) {
                Notices::init();
            }
        }

        if ($errors || $warnings) {
            $self = $this;
            add_action('admin_notices', function() use($self, $errors, $warnings) {
                $self->showNotices($errors, 'error');
                $self->showNotices($warnings, 'warning');
            });
        }

        return array($errors, $warnings);
    }

    private function showNotices($notices, $kind)
    {
        foreach ($notices as $dismissId => $notice) {

            $dismissClass = null;
            $dismissAttr = null;
            if (is_string($dismissId) && !empty($dismissId)) {
                $dismissClass = "is-dismissible";
                $dismissAttr = "data-dismissible=".esc_html($dismissId);
            }

            ?>
                <div class="notice notice-<?php echo esc_html($kind) ?> <?php echo $dismissClass ?>"
                    <?php echo $dismissAttr ?>
                >
                    <p>
                        <?php if ($kind === 'error'): ?>
                            <b><?= esc_html($this->plugin) ?> disabled.</b><br>
                        <?php endif; ?>
                        <?php echo esc_html($notice) ?>
                    </p>
                </div>
            <?php
        }
    }

    /**
     * @param string $required
     * @param string $pluginName
     * @return string
     */
    private static function checkWcVersion($required, $pluginName)
    {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }

        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            return
                "$pluginName could not locate WooCommerce. ".
                'Make sure WooCommerce is installed in the standard folder and activated.';
        }

        $data = get_file_data(WP_PLUGIN_DIR.'/woocommerce/woocommerce.php', array('Version'));
        $v = $data[0];
        if (!$v) {
            return "$pluginName failed to detect WooCommerce version.";
        }

        if (version_compare($v, $required) < 0) {
            return
                "You are running an outdated WooCommerce version $v. ".
                "$pluginName requires WooCommerce $required+. ".
                'Please update to a modern WooCommerce version.';
        }

        return '';
    }

    private static function isZendGuardLoaderActive()
    {
        return
            in_array('Zend Guard Loader', get_loaded_extensions(), true) &&
            self::getPhpIniBool('zend_loader.enable', true);
    }

    /** @noinspection PhpSameParameterValueInspection */
    private static function getPhpIniBool($name, $default = null)
    {
        $value = ini_get($name);

        if ($value === false) {
            return $default;
        }

        if ((int)$value > 0) {

            $value = true;

        } else {

            $lowered = strtolower($value);

            if (in_array($lowered, array('true', 'on', 'yes'), true)) {
                $value = true;
            } else {
                $value = false;
            }
        }

        return $value;
    }
}