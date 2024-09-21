<?php

declare(strict_types=1);

use Twint\Command\PollCommand;
use Twint\Woo\Api\Admin\GetTransactionLogAction;
use Twint\Woo\Api\Admin\StoreConfigurationAction;
use Twint\Woo\Api\Frontend\ExpressCheckoutAction;
use Twint\Woo\API\Frontend\OrderPayButtonAction;
use Twint\Woo\Api\Frontend\PaymentStatusAction;
use Twint\Woo\Container\ContainerInterface;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Logger\NullLogger;
use Twint\Woo\Model\Button\ExpressButton;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Model\Modal\Spinner;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\AppsService;
use Twint\Woo\Service\ExpressCheckoutService;
use Twint\Woo\Service\FastCheckoutCheckinService;
use Twint\Woo\Service\MonitorService;
use Twint\Woo\Service\PairingService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Service\SettingService;
use Twint\Woo\TwintIntegration;
use Twint\Woo\Utility\CertificateHandler;
use Twint\Woo\Utility\CredentialsValidator;
use Twint\Woo\Utility\CryptoHandler;

function twint_services()
{
    return [
        //Logger
        'logger' => static function (ContainerInterface $container) {
            if (!function_exists('wc_get_logger')) {
                return new NullLogger();
            }

            return wc_get_logger();
        },
        // Repositories
        'pairing.repository' => static function (ContainerInterface $container) {
            return new PairingRepository();
        },
        'transaction.repository' => static function (ContainerInterface $container) {
            return new TransactionRepository();
        },
        //Commands
        'poll.command' => static function (ContainerInterface $container) {
            return new PollCommand(
                $container->get('pairing.repository'),
                $container->get('monitor.service'),
                $container->get('logger'),
            );
        },
        //Handlers
        'crypto.handler' => static function (ContainerInterface $container) {
            return new CryptoHandler();
        },
        'certificate.handler' => static function (ContainerInterface $container) {
            return new CertificateHandler();
        },
        'credentials.validator' => static function (ContainerInterface $container) {
            return new CredentialsValidator($container->get('crypto.handler'));
        },
        'client.builder' => static function (ContainerInterface $container) {
            return new ClientBuilder($container->get('crypto.handler'), $container->get('setting.service'));
        },
        // Base
        'twint.integration' => static function (ContainerInterface $container) {
            return new TwintIntegration(
                $container->get('payment.service'),
                $container->get('pairing.service'),
                $container->get('api.service'),
                $container->get('pairing.repository'),
            );
        },

        // Services
        'pairing.service' => static function (ContainerInterface $container) {
            return new PairingService(
                $container->get('client.builder'),
                $container->get('api.service'),
                $container->get('logger'),
            );
        },
        'payment.service' => static function (ContainerInterface $container) {
            return new PaymentService(
                $container->get('client.builder'),
                $container->get('api.service'),
                $container->get('pairing.repository'),
                $container->get('logger'),
            );
        },
        'api.service' => static function (ContainerInterface $container) {
            return new ApiService($container->get('logger'));
        },
        'apps.service' => static function (ContainerInterface $container) {
            return new AppsService($container->get('client.builder'));
        },
        'setting.service' => static function (ContainerInterface $container) {
            return new SettingService();
        },
        'monitor.service' => static function (ContainerInterface $container) {
            return new MonitorService(
                $container->get('pairing.repository'),
                $container->get('pairing.service'),
                $container->get('logger'),
            );
        },
        'express_checkout.service' => static function (ContainerInterface $container): ExpressCheckoutService {
            return new ExpressCheckoutService();
        },
        'fast_checkout_checkin.service' => static function (ContainerInterface $container): FastCheckoutCheckinService {
            return new FastCheckoutCheckinService(
                $container->get('logger'),
                $container->get('client.builder'),
                $container->get('api.service'),
                $container->get('pairing.service'),
            );
        },
        // Actions
        'get_transaction_log.action' => static function (ContainerInterface $container) {
            return new GetTransactionLogAction($container->get('transaction.repository'));
        },
        'store_configuration.action' => static function (ContainerInterface $container) {
            return new StoreConfigurationAction(
                $container->get('crypto.handler'),
                $container->get('credentials.validator'),
                $container->get('logger'),
                $container->get('setting.service'),
                $container->get('certificate.handler'),
            );
        },
        'payment_status.action' => static function (ContainerInterface $container) {
            return new PaymentStatusAction($container->get('pairing.repository'), $container->get('logger'));
        },
        'express_checkout.action' => static function (ContainerInterface $container) {
            return new ExpressCheckoutAction(
                $container->get('express_checkout.service')
            );
        },

        // Express Checkout
        'express.button' => static function (ContainerInterface $container) {
            return new ExpressButton(
                $container->get('setting.service'),
                $container->get('payment.modal'),
                $container->get('express.spinner'),
            );
        },
        'express.spinner' => static function (ContainerInterface $container): Spinner {
            return new Spinner();
        },
        'payment.modal' => static function (ContainerInterface $container): Modal {
            return new Modal($container->get('apps.service'));
        },
    ];
}
