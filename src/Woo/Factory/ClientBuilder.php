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
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\PrefixedCashRegisterId;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Exception\InvalidConfigException;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Utility\CryptoHandler;

class ClientBuilder
{
    private static InvocationRecordingClient $instance;

    public function __construct(
        private readonly CryptoHandler $crypto,
        private readonly SettingService $setting,
    ) {
    }

    public function build(int $version = Version::LATEST): InvocationRecordingClient
    {
        // SINGLETON check
        if (isset(self::$instance)) {
            return self::$instance;
        }

        $environment = $this->setting->isTestMode() ? Environment::TESTING() : Environment::PRODUCTION();
        $storeUuid = $this->setting->getStoreUuid();
        if ($storeUuid === null || $storeUuid === '' || $storeUuid === '0') {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_STORE_UUID);
        }

        $certificate = $this->setting->getCertificate();
        if ($certificate === null || $certificate === []) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
        }

        try {
            $cert = $this->crypto->decrypt($certificate['certificate']);
            $passphrase = $this->crypto->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $passphrase === '0' || ($cert === '' || $cert === '0')) {
                throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
            }
            $messageRecorder = new MessageRecorder();

            $client = new InvocationRecordingClient(
                new Client(
                    CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                    new PrefixedCashRegisterId(StoreUuid::fromString($storeUuid), TwintConstant::PLATFORM),
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
