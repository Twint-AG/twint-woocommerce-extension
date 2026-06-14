<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use DateTimeImmutable;
use Twint\Sdk\Diagnostics\Collector;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use Twint\Woo\Template\Admin\Setting\Tab\Diagnostics;

class DiagnosticService
{
    private PairingRepository $pairingRepository;

    private TransactionRepository $transactionRepository;

    public function __construct(PairingRepository $pairingRepository, TransactionRepository $transactionRepository)
    {
        $this->pairingRepository = $pairingRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Build a collector with all global diagnostic files and system information.
     */
    private function buildGlobalCollector(): Collector
    {
        $collector = Collector::withDefaults(new DateTimeImmutable());
        $collector = $collector->includePath(WP_CONTENT_DIR . '/debug.log');

        // Add PHP error log file if available
        $phpErrorLog = ini_get('error_log');
        if (
            is_string($phpErrorLog) && $phpErrorLog !== '' && strtolower($phpErrorLog) !== 'syslog'
            && @is_file($phpErrorLog) && @is_readable($phpErrorLog)
        ) {
            $collector = $collector->includePath($phpErrorLog);
        }

        if (defined('WC_LOG_DIR') && WC_LOG_DIR) {
            $collector = $collector
                ->includePath(WC_LOG_DIR, static fn (string $path) => str_ends_with($path, '.log'));
        }

        $info = Diagnostics::getInformation();
        foreach ($info as $item) {
            $collector = $collector->includeInsight($item['label'], $item['value']);
        }

        return $collector;
    }

    public function downloadGlobalDiagnostics(): void
    {
        if (!current_user_can('activate_plugins')) {
            wp_die('You are not allowed to download diagnostics.', '', [
                'response' => 403,
            ]);
        }

        if (
            !isset($_POST['twint_download_diagnostics_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['twint_download_diagnostics_nonce'])),
                'twint_download_diagnostics'
            )
        ) {
            wp_die('Security check failed.');
        }

        $collector = $this->buildGlobalCollector();

        // Set headers for file download
        $timestamp = date('Ymd_His');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="twint-woocommerce-diagnostics-' . $timestamp . '.zip"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $collector->collect(
            fileNamePrefix: 'twint-woocommerce-diagnostics',
            streamHandler: static function ($data): void {
                print $data;
            }
        );

        exit; // Prevent any additional output
    }

    public function downloadOrderDiagnostics(): void
    {
        if (!current_user_can('activate_plugins')) {
            wp_die('You are not allowed to download diagnostics.', '', [
                'response' => 403,
            ]);
        }

        if (
            !isset($_REQUEST['twint_download_order_diagnostics_nonce'], $_REQUEST['order_id']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_REQUEST['twint_download_order_diagnostics_nonce'])),
                'twint_download_order_diagnostics'
            )
        ) {
            wp_die('Security check failed.');
        }

        $orderId = (int) $_REQUEST['order_id'];
        $order = wc_get_order($orderId);

        if (!$order) {
            wp_die('Order not found.');
        }

        // Start with all global diagnostic files
        $collector = $this->buildGlobalCollector();
        $timestamp = date('Ymd_His');

        // Use WooCommerce log directory for temporary files
        $baseDir = defined('WC_LOG_DIR') ? WC_LOG_DIR : WP_CONTENT_DIR;

        // Order Basic Info
        $collector = $collector->includeInsight('Order ID', (string) $order->get_id());
        $collector = $collector->includeInsight('Order Number', $order->get_order_number());
        $collector = $collector->includeInsight('Status', $order->get_status());
        $collector = $collector->includeInsight('Transaction ID', (string) $order->get_transaction_id());
        $collector = $collector->includeInsight('Total', (string) $order->get_total());
        $collector = $collector->includeInsight('Currency', $order->get_currency());

        $pairings = $this->pairingRepository->findByWooOrderId($orderId);

        $pairingData = array_map(static fn ($p) => $p->toArray(), $pairings);

        $pairingFile = $baseDir . "/twint_pairing_order_{$orderId}_{$timestamp}.json";
        file_put_contents($pairingFile, json_encode($pairingData, JSON_PRETTY_PRINT));
        $collector = $collector->includePath($pairingFile);

        $logs = $this->transactionRepository->getByOrderId($orderId);

        $logData = array_map(static fn ($l) => $l->toArray(), $logs);

        $logFile = $baseDir . "/twint_trans_log_order_{$orderId}_{$timestamp}.json";
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
        $collector = $collector->includePath($logFile);

        // Set headers for file download
        header('Content-Type: application/zip');
        header(
            'Content-Disposition: attachment; filename="twint-order-diagnostics-' . $orderId . '-' . $timestamp . '.zip"'
        );
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            $collector->collect(
                fileNamePrefix: "twint-order-diagnostics-{$orderId}-{$timestamp}",
                streamHandler: static function ($data): void {
                    print $data;
                }
            );
        } finally {
            if (file_exists($pairingFile)) {
                wp_delete_file($pairingFile);
            }
            if (file_exists($logFile)) {
                wp_delete_file($logFile);
            }
        }

        exit; // Prevent any additional output
    }
}
