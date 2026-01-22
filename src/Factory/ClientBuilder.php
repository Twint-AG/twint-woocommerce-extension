<?php

declare(strict_types=1);

namespace Twint\Woo\Factory;

use Soap\Engine\Transport;
use Throwable;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Factory\DefaultSoapEngineFactory;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Soap\MessageRecorder;
use Twint\Sdk\InvocationRecorder\Soap\RecordingTransport;
use Twint\Sdk\Io\FileWriterStack;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Io\TemporaryFileWriter;
use Twint\Sdk\Io\TemporaryFileWriterGuesser;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\ExistingPath;
use Twint\Sdk\Value\PlatformVersion;
use Twint\Sdk\Value\PluginVersion;
use Twint\Sdk\Value\ShopPlatform;
use Twint\Sdk\Value\ShopPluginInformation;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Exception\InvalidConfigException;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Utility\CryptoHandler;
use Twint\Woo\Utility\VersionTrait;

/**
 * @method SettingService getSetting()
 * @method CryptoHandler getCrypto()
 */
class ClientBuilder
{
    use VersionTrait;
    use LazyLoadTrait;

    protected static array $lazyLoads = ['crypto', 'setting'];

    private static InvocationRecordingClient $instance;

    public function __construct(
        private Lazy|CryptoHandler  $crypto,
        private Lazy|SettingService $setting,
    ) {
    }

    public function build(int $version = Version::LATEST): InvocationRecordingClient
    {
        // SINGLETON check
        if (isset(self::$instance)) {
            return self::$instance;
        }

        $environment = $this->getSetting()->isTestMode() ? Environment::TESTING() : Environment::PRODUCTION();
        $storeUuid = $this->getSetting()->getStoreUuid();
        if (in_array($storeUuid, [null, '', '0'], true)) {
            throw new InvalidConfigException(esc_html(InvalidConfigException::ERROR_INVALID_STORE_UUID));
        }

        $certificate = $this->getSetting()->getCertificate();
        if ($certificate === null || $certificate === []) {
            throw new InvalidConfigException(esc_html(InvalidConfigException::ERROR_INVALID_CERTIFICATE));
        }

        try {
            $cert = $this->getCrypto()->decrypt($certificate['certificate']);
            $passphrase = $this->getCrypto()->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $passphrase === '0' || ($cert === '' || $cert === '0')) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
            }
            $messageRecorder = new MessageRecorder();

            $uploadDir = wp_upload_dir();
            $client = new InvocationRecordingClient(
                new Client(
                    CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                    new ShopPluginInformation(
                        StoreUuid::fromString($storeUuid),
                        ShopPlatform::WOOCOMMERCE(),
                        PlatformVersion::multiple(...$this->getSystemVersions()),
                        new PluginVersion(TwintConstant::PLUGIN_VERSION),
                        TwintConstant::installSource()
                    ),
                    new Version($version),
                    $environment,
                    new FileWriterStack(
                        [
                            ...TemporaryFileWriterGuesser::createDefaultStack(),
                            static fn () => new TemporaryFileWriter(new ExistingPath($uploadDir['path'])),
                        ]
                    ),
                    soapEngineFactory: new DefaultSoapEngineFactory(
                        wrapTransport: static fn (Transport $transport) => new RecordingTransport(
                            $transport,
                            $messageRecorder
                        )
                    )
                ),
                $messageRecorder
            );

            self::$instance = $client;

            return $client;
        } catch (Throwable $e) {
            throw new InvalidConfigException(esc_html(InvalidConfigException::ERROR_UNDEFINED), 0);
        }
    }
}
