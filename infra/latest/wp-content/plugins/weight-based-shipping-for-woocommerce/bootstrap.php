<?php declare(strict_types=1);

use Wbs\Plugin;


call_user_func(function() {

    $pluginEntryFile = wp_normalize_path(__DIR__.'/plugin.php');


    global $gzp_wbs_active_installations;

    $insts = &$gzp_wbs_active_installations;
    if (!is_array($insts)) {
        $insts = array();
    }

    $insts[$pluginEntryFile] = true;

    register_activation_hook($pluginEntryFile, function() use ($pluginEntryFile, $insts) {
        unset($insts[$pluginEntryFile]);
        if ($insts) {
            deactivate_plugins(array_keys($insts));
        }
    });

    if (count($insts) === 2) {
        add_action('admin_notices', function() use ($pluginEntryFile) {
            $plugin = get_file_data($pluginEntryFile, ['Plugin Name'])[0];
            ?>
            <div class="notice notice-error">
                <p>
                    Multiple active <?= esc_html($plugin) ?> installations detected.
                    All except one are temporarily deactivated to prevent possible errors.
                    Please deactivate the installations you do not need manually.
                </p>
            </div>
            <?php
        });
    }

    if (count($insts) > 1) {
        return;
    }


    if (!class_exists(Plugin::class, false)) {
        require_once(__DIR__."/server/vendor/autoload.php");
        Plugin::setupOnce($pluginEntryFile);
    }

    file_exists($f = __DIR__.'/server/wbsng/plugin.php') and require($f);
});