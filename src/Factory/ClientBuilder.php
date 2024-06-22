<?php

declare(strict_types=1);

namespace TWINT\Factory;

use Soap\Engine\Transport;
use Throwable;
use TWINT\Exception\InvalidConfigException;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Factory\DefaultSoapEngineFactory;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Soap\MessageRecorder;
use Twint\Sdk\InvocationRecorder\Soap\RecordingTransport;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\Version;
use TWINT\Services\SettingService;
use TWINT\Utility\Twint\CryptoHandler;

class ClientBuilder
{
    public static $instance;

    /**
     * @var CryptoHandler
     */
    private CryptoHandler $crypto;

    /**
     * @var SettingService
     */
    private SettingService $setting;

    public function __construct() {
        $this->crypto = new CryptoHandler();
        $this->setting = new SettingService();
    }

    public function build(int $version = Version::LATEST): InvocationRecordingClient
    {
        // SINGLETON check
        if (isset(self::$instance)) {
            return self::$instance;
        }

        $environment = $this->setting->isTestMode() ? Environment::TESTING() : Environment::PRODUCTION();
        $merchantId = $this->setting->getMerchantId();
        if (empty($merchantId)) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_MERCHANT_ID);
        }

        $certificate = $this->setting->getCertificate();
        if (empty($certificate)) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
        }

        try {
            $cert = $this->crypto->decrypt($certificate['certificate']);
            $passphrase = $this->crypto->decrypt($certificate['passphrase']);

            if (empty($passphrase) || empty($cert)) {
                throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
            }
            $messageRecorder = new MessageRecorder();

            $client = new InvocationRecordingClient(
                new Client(
                    CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                    MerchantId::fromString($merchantId),
                    new Version($version),
                    $environment,
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
            throw new InvalidConfigException(InvalidConfigException::ERROR_UNDEFINED, 0, $e);
        }
    }
}
