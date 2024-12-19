<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

use Exception;
use Throwable;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\PlatformVersion;
use Twint\Sdk\Value\PluginVersion;
use Twint\Sdk\Value\ShopPlatform;
use Twint\Sdk\Value\ShopPluginInformation;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Constant\TwintConstant;

class CredentialsValidator implements CredentialValidatorInterface
{
    use VersionTrait;

    public function __construct(
        private readonly CryptoHandler $crypto
    ) {
    }

    public function validate(?array $certificate, string $storeUuid, bool $testMode): bool
    {
        try {
            $cert = $this->crypto->decrypt($certificate['certificate'] ?? '');
            $passphrase = $this->crypto->decrypt($certificate['passphrase'] ?? '');

            if ($passphrase === '' || $cert === '') {
                return false;
            }

            $client = new Client(
                CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                new ShopPluginInformation(
                    StoreUuid::fromString($storeUuid),
                    ShopPlatform::WOOCOMMERCE(),
                    new PlatformVersion(...$this->getSystemVersions()),
                    new PluginVersion(TwintConstant::PLUGIN_VERSION),
                    TwintConstant::installSource()
                ),
                Version::latest(),
                $testMode ? Environment::TESTING() : Environment::PRODUCTION(),
            );
            $status = $client->checkSystemStatus();
        } catch (Exception|SdkError $e) {
            error_log($this->buildLogMessage($e));
            return false;
        }

        return $status->isOk();
    }

    private function buildLogMessage(Throwable $e, string $message = ''): string
    {
        // Set a default message if none is provided
        if ($message === '' || $message === '0') {
            $message = 'TWINT certificate error: ' . $e->getMessage();
        }

        // Append details about previous exceptions recursively
        $previous = $e->getPrevious();
        if ($previous instanceof Throwable) {
            $message .= sprintf(
                "\n %s:%d %s -> %s",
                $previous->getFile(),
                $previous->getLine(),
                get_class($previous),
                $this->buildLogMessage($previous)
            );
        }

        return $message;
    }
}
