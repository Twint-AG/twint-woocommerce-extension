<?php

namespace TWINT\Woo\App\API;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Services\TransactionLogService;

class TwintApiWordpressAjax
{

    private \Twig\Environment $template;

    public function __construct()
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;
        add_action('wp_ajax_get_log_transaction_details', [$this, 'getLogTransactionDetails']);
        add_action('wp_ajax_nopriv_get_log_transaction_details', [$this, 'pleaseLogin']);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function getLogTransactionDetails()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'get_log_transaction_details')) {
            exit('The WP Nonce is invalid, please check again!');
        }
        $transactionLogService = new TransactionLogService();
        $data = $transactionLogService->getLogTransactionDetails($_REQUEST['record_id']);
        $template = $this->template->load('Layouts/partials/modal/details-content.html.twig');

        $result = $template->render($data);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        die();
    }

    public function pleaseLogin(): void
    {
        echo 'You must log in to like';
        die();
    }
}