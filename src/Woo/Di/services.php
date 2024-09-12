<?php

use Twint\Command\PollCommand;
use Twint\Woo\Api\Admin\GetTransactionLogAction;
use Twint\Woo\Api\Admin\StoreConfigurationAction;
use Twint\Woo\Api\Frontend\PairingMonitoringAction;
use Twint\Woo\Container\ContainerInterface;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\AppsService;
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
        'logger' => function (ContainerInterface $container) {
            if(!function_exists('wc_get_logger')){
                return new Twint\Woo\Logger\NullLogger();
            }

            return wc_get_logger() ;
        },
        // Repositories
        'pairing.repository' => function (ContainerInterface $container) {
            return new PairingRepository();
        },
        'transaction.repository' => function (ContainerInterface $container) {
            return new TransactionRepository();
        },
        //Commands
        'poll.command' => function (ContainerInterface $container) {
            return new PollCommand(
                $container->get('pairing.repository'),
                $container->get('monitor.service'),
                $container->get('logger'),
            );
        },
        //Handlers
        'crypto.handler' => function (ContainerInterface $container) {
            return new CryptoHandler();
        },
        'certificate.handler' => function (ContainerInterface $container) {
            return new CertificateHandler();
        },
        'credentials.validator' => function (ContainerInterface $container) {
            return new CredentialsValidator(
                $container->get('crypto.handler'),
            );
        },
        'client.builder' => function (ContainerInterface $container) {
            return new ClientBuilder(
                $container->get('crypto.handler'),
                $container->get('setting.service'),
            );
        },
        //Base
        'twint.integration' => function (ContainerInterface $container) {
            return new TwintIntegration(
                $container->get('payment.service'),
                $container->get('pairing.service'),
                $container->get('api.service'),
                $container->get('pairing.repository'),
            );
        },

        // Services
        'pairing.service' => function (ContainerInterface $container) {
            return new PairingService(
                $container->get('client.builder'),
                $container->get('api.service'),
                $container->get('logger'),
            );
        },
        'payment.service' => function (ContainerInterface $container) {
            return new PaymentService(
                $container->get('client.builder'),
                $container->get('api.service'),
                $container->get('pairing.repository'),
                $container->get('logger'),
            );
        },
        'api.service' => function (ContainerInterface $container) {
            return new ApiService(
                $container->get('logger')
            );
        },
        'apps.service' => function (ContainerInterface $container) {
            return new AppsService(
                $container->get('client.builder')
            );
        },
        'setting.service' => function (ContainerInterface $container) {
            return new SettingService();
        },
        'monitor.service' => function (ContainerInterface $container) {
            return new MonitorService(
                $container->get('pairing.repository'),
                $container->get('pairing.service'),
                $container->get('logger'),
            );
        },
        // Actions
        'get_transaction_log.action' => function (ContainerInterface $container) {
            return new GetTransactionLogAction(
                $container->get('transaction.repository'),
            );
        },
        'store_configuration.action' => function (ContainerInterface $container) {
            return new StoreConfigurationAction(
                $container->get('crypto.handler'),
                $container->get('credentials.validator'),
                $container->get('logger'),
                $container->get('setting.service'),
                $container->get('certificate.handler'),
            );
        },
        'monitor_pairing.action' => function (ContainerInterface $container) {
            return new PairingMonitoringAction(
                $container->get('pairing.repository'),
                $container->get('logger'),
            );
        }
    ];
}
