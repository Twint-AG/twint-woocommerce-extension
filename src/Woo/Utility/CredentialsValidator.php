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
use Twint\Sdk\Value\PrefixedCashRegisterId;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Service\SettingService;

class CredentialsValidator implements CredentialValidatorInterface
{
    public function __construct(
        private readonly CryptoHandler $crypto
    )
    {
    }

    /**
     * @param array|null $certificate
     * @param string $storeUuid
     * @param bool $testMode
     * @return bool
     */
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
                new PrefixedCashRegisterId(StoreUuid::fromString($storeUuid), SettingService::PLATFORM),
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
