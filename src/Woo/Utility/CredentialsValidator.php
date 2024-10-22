<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

use Exception;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Exception\SdkError;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
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
                    $this->getSystemVersions(),
                    TwintConstant::PLUGIN_VERSION,
                    TwintConstant::installSource()
                ),
                Version::latest(),
                $testMode ? Environment::TESTING() : Environment::PRODUCTION(),
            );
            $status = $client->checkSystemStatus();
        } catch (Exception|SdkError $e) {
            return false;
        }

        return $status->isOk();
    }
}
