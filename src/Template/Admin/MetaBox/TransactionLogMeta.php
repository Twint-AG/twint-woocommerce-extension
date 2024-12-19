<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\MetaBox;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\TransactionRepository;
use WC_Order;

class TransactionLogMeta
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'addShopOrderMetaBoxesTwintApiResponse']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addShopOrderMetaBoxesTwintApiResponse(): void
    {
        // Support latest / oldest (none-blocks and blocks)
        $screen = class_exists(CustomOrdersTableController::class) && wc_get_container()
            ->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';
        add_meta_box(
            'woocommerce-order-twint-transaction-log',
            __('Transaction logs', 'twint-woocommerce-extension'),
            [$this, 'addTransactionLogTable'],
            $screen,
            'normal',
            'core'
        );
    }

    public function addTransactionLogTable($post): void
    {
        $order = wc_get_order($post instanceof WC_Order ? $post->get_id() : $post->ID);

        /** @var TransactionRepository $repository */
        $repository = Plugin::di('transaction.repository', true);
        $logs = $repository->getByOrderId($order->get_id());

        $nonce = wp_create_nonce('get_log_transaction_details');

        require Plugin::abspath() . 'src/View/Admin/transaction_log.php';
    }
}
