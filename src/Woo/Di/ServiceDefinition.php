<?php

declare(strict_types=1);

namespace Twint\Woo\Di;

use Twint\Command\PollCommand;
use Twint\Woo\Api\Admin\GetTransactionLogAction;
use Twint\Woo\Api\Admin\StoreConfigurationAction;
use Twint\Woo\Api\Frontend\CancelPaymentAction;
use Twint\Woo\Api\Frontend\ExpressCheckoutAction;
use Twint\Woo\Api\Frontend\PaymentStatusAction;
use Twint\Woo\Container\ContainerInterface;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Logger\NullLogger;
use Twint\Woo\Model\Button\ExpressButton;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Model\Modal\Spinner;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\AppsService;
use Twint\Woo\Service\Express\ExpressOrderService;
use Twint\Woo\Service\ExpressCheckoutService;
use Twint\Woo\Service\FastCheckoutCheckinService;
use Twint\Woo\Service\MonitorService;
use Twint\Woo\Service\PairingService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Setup\CliSupportTrigger;
use Twint\Woo\Setup\Installer;
use Twint\Woo\Setup\Migration\AddReferenceIdColumnToPairingTable;
use Twint\Woo\Setup\Migration\CreatePairingTable;
use Twint\Woo\Setup\Migration\CreateTransactionLogTable;
use Twint\Woo\Setup\UnInstaller;
use Twint\Woo\TwintIntegration;
use Twint\Woo\Utility\CertificateHandler;
use Twint\Woo\Utility\CredentialsValidator;
use Twint\Woo\Utility\CryptoHandler;
use wpdb;

class ServiceDefinition
{
    public static function services()
    {
        return [
            'installer' => static fn (ContainerInterface $container): Installer => new Installer(
                $container->get('migrations'),
                $container->get('cli.trigger')
            ),
            'uninstaller' => static fn (ContainerInterface $container): UnInstaller => new UnInstaller($container->get(
                'migrations'
            )),
            'db' => static function (ContainerInterface $container): wpdb {
                global $wpdb;

                return $wpdb;
            },
            'pairing.migration' => static fn (ContainerInterface $container): CreatePairingTable => new CreatePairingTable(
                $container->get('db')
            ),
            'log.migration' => static fn (ContainerInterface $container): CreateTransactionLogTable => new CreateTransactionLogTable(
                $container->get('db')
            ),
            'migrations' => static fn (ContainerInterface $container): array => [
                $container->get('pairing.migration'),
                $container->get('log.migration'),
                new AddReferenceIdColumnToPairingTable($container->get('db')),
            ],

            //Logger
            'logger' => static function (ContainerInterface $container) {
                if (!function_exists('wc_get_logger')) {
                    return new NullLogger();
                }

                return wc_get_logger();
            },
            'cli.trigger' => static fn (ContainerInterface $ci) => new CliSupportTrigger($ci->get('logger')),
            // Repositories
            'pairing.repository' => static fn (ContainerInterface $container) => new Lazy(
                static fn () => new PairingRepository($container->get('db'), $container->get('logger'))
            ),
            'transaction.repository' => static fn (ContainerInterface $container) => new TransactionRepository(
                $container->get('db'),
                $container->get('logger')
            ),
            //Commands
            'poll.command' => static fn (ContainerInterface $container) => new PollCommand(),
            //Handlers
            'crypto.handler' => static fn (ContainerInterface $container) => new CryptoHandler(),
            'certificate.handler' => static fn (ContainerInterface $container) => new CertificateHandler(),
            'credentials.validator' => static fn (ContainerInterface $container) => new Lazy(static fn () => new CredentialsValidator(
                $container->get('crypto.handler')
            )),
            'client.builder' => static fn (ContainerInterface $container) => new Lazy(
                static fn () => new ClientBuilder($container->get('crypto.handler'), $container->get('setting.service'))
            ),
            // Base
            'twint.integration' => static function (ContainerInterface $container) {
                return new TwintIntegration(
                    $container->get('payment.service'),
                    $container->get('api.service'),
                    $container->get('pairing.repository'),
                );
            },

            // Services
            'pairing.service' => static fn (ContainerInterface $container) => new Lazy(
                static fn () => new PairingService(
                    $container->get('pairing.repository'),
                    $container->get('transaction.repository'),
                    $container->get('client.builder'),
                    $container->get('api.service'),
                    $container->get('logger'),
                )
            ),
            'payment.service' => static fn (ContainerInterface $container) => new Lazy(
                static fn () => new PaymentService(
                    $container->get('client.builder'),
                    $container->get('api.service'),
                    $container->get('pairing.repository'),
                    $container->get('logger'),
                )
            ),
            'api.service' => static fn (ContainerInterface $container) => new ApiService($container->get(
                'logger'
            ), $container->get('transaction.repository')),
            'apps.service' => static fn (ContainerInterface $container) => new AppsService($container->get(
                'client.builder'
            )),
            'setting.service' => static fn (ContainerInterface $container) => new SettingService(),
            'monitor.service' => static function (ContainerInterface $container) {
                return new MonitorService(
                    $container->get('pairing.repository'),
                    $container->get('transaction.repository'),
                    $container->get('client.builder'),
                    $container->get('logger'),
                    $container->get('pairing.service'),
                    $container->get('api.service'),
                    $container->get('express_order.service'),
                );
            },
            'express_checkout.service' => static fn (ContainerInterface $container): ExpressCheckoutService => new ExpressCheckoutService(),
            'fast_checkout_checkin.service' => static function (
                ContainerInterface $container
            ): FastCheckoutCheckinService {
                return new FastCheckoutCheckinService(
                    $container->get('logger'),
                    $container->get('client.builder'),
                    $container->get('api.service'),
                    $container->get('pairing.service'),
                );
            },
            'express_order.service' => static fn (ContainerInterface $container): Lazy => new Lazy(
                static fn () => new ExpressOrderService(
                    $container->get('pairing.repository'),
                    $container->get('api.service'),
                    $container->get('logger'),
                    $container->get('client.builder'),
                    $container->get('pairing.service')
                )
            ),
            // Actions
            'get_transaction_log.action' => static fn (ContainerInterface $container) => new GetTransactionLogAction(
                $container->get('transaction.repository')
            ),
            'store_configuration.action' => static fn (ContainerInterface $container) => new StoreConfigurationAction(
                $container->get('crypto.handler'),
                $container->get('credentials.validator'),
                $container->get('logger'),
                $container->get('setting.service'),
                $container->get('certificate.handler'),
            ),
            'payment_status.action' => static function (ContainerInterface $container) {
                return new PaymentStatusAction(
                    $container->get('pairing.repository'),
                    $container->get('monitor.service'),
                    $container->get('logger')
                );
            },
            'payment_cancel.action' => static function (ContainerInterface $container) {
                return new CancelPaymentAction(
                    $container->get('pairing.repository'),
                    $container->get('monitor.service'),
                    $container->get('logger')
                );
            },
            'express_checkout.action' => static fn (ContainerInterface $container) => new ExpressCheckoutAction(
                $container->get('express_checkout.service'),
                $container->get('monitor.service'),
            ),

            // Express Checkout
            'express.button' => static function (ContainerInterface $container) {
                return new ExpressButton(
                    $container->get('setting.service'),
                    $container->get('payment.modal'),
                    $container->get('express.spinner'),
                );
            },
            'express.spinner' => static fn (ContainerInterface $container): Spinner => new Spinner(),
            'payment.modal' => static fn (ContainerInterface $container): Modal => new Modal($container->get(
                'apps.service'
            )),
        ];
    }
}
