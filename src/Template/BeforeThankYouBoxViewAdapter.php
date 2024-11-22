<?php

declare(strict_types=1);

namespace Twint\Woo\Template;

use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\PairingRepository;
use WC_Order;

class BeforeThankYouBoxViewAdapter
{
    public function __construct(
        private readonly WC_Order          $order,
        private readonly PairingRepository $pairingRepository,
    ) {
    }

    public function render(): void
    {
        $pairing = $this->pairingRepository->get($this->order->get_transaction_id());
        if (!$pairing instanceof Pairing) {
            return;
        }

        $paid = $pairing->isSuccessful();
        $cancelled = !empty($_GET['twint_order_cancelled']) && filter_var(
            wp_unslash($_GET['twint_order_cancelled']),
            FILTER_VALIDATE_BOOLEAN
        ) || $this->order->get_status() === RegularCheckoutGateway::getOrderStatusAfterCancelled();

        if ($paid) {
            require Plugin::abspath() . 'src/View/Frontend/paid.php';
        }

        if ($cancelled) {
            require Plugin::abspath() . 'src/View/Frontend/unpaid.php';
        }
    }
}
