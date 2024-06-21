<?php

declare(strict_types=1);

namespace TWINT\Utility\Twint;

use Exception;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\Version;

class CredentialValidator implements CredentialValidatorInterface
{
    /**
     * @var CryptoHandler
     */
    private CryptoHandler $crypto;

    public function __construct()
    {
        $this->crypto = new CryptoHandler();
    }

    public function validate(array $certificate, string $merchantId, bool $testMode): bool
    {
        try {
            $cert = $this->crypto->decrypt($certificate['certificate']);
            $passphrase = $this->crypto->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $cert === '') {
                return false;
            }

            $client = new Client(
                CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                MerchantId::fromString($merchantId),
                Version::latest(),
                $testMode ? Environment::TESTING() : Environment::PRODUCTION(),
            );
            $status = $client->checkSystemStatus();
        } catch (Exception $e) {
            return false;
        }

        return $status->isOk();
    }
}
