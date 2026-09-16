<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

use Exception;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Exception\InvalidCertificate;
use Twint\Sdk\Io\InMemoryStream;

class CertificateHandler
{
    public const ERROR_INVALID_UNKNOWN = 'ERROR_INVALID_UNKNOWN';

    public const ERROR_INVALID_INPUT = 'ERROR_INVALID_INPUT';

    public function __construct(
        private ?LegacyPkcs12Converter $converter = null
    ) {
    }

    public function read(string $pkcs12, string $password): Pkcs12Certificate|string
    {
        if ($pkcs12 === '' || $password === '') {
            return self::ERROR_INVALID_INPUT;
        }

        try {
            return $this->establishTrust($pkcs12, $password);
        } catch (InvalidCertificate $e) {
            // OpenSSL 3.x refuses legacy-encoded PKCS#12 files (RC2-40 / 3DES)
            // because the legacy provider is not loaded by default, failing with
            // "digital envelope routines::unsupported". Try to normalise the
            // certificate to a modern format in a subprocess and read it again
            // before giving up.
            $recovered = $this->tryLegacyConversion($pkcs12, $password);
            if ($recovered instanceof Pkcs12Certificate) {
                return $recovered;
            }

            $codes = CertificateErrorMapper::getErrorCodes();
            $error = $e->getErrors()[0];

            return $codes[$error] ?? static::ERROR_INVALID_UNKNOWN;
        } catch (Exception $e) {
            return self::ERROR_INVALID_UNKNOWN;
        }
    }

    private function establishTrust(string $pkcs12, string $password): Pkcs12Certificate
    {
        return Pkcs12Certificate::establishTrust(new InMemoryStream($pkcs12), $password, new Clock());
    }

    /**
     * Attempt to recover a legacy-encoded certificate by converting it to a
     * modern format (needs a subprocess; see LegacyPkcs12Converter).
     */
    private function tryLegacyConversion(string $pkcs12, string $password): ?Pkcs12Certificate
    {
        if (!$this->converter instanceof LegacyPkcs12Converter || !$this->converter->canRun()) {
            return null;
        }

        $modern = $this->converter->toModern($pkcs12, $password);
        if ($modern === null || $modern === '') {
            return null;
        }

        try {
            return $this->establishTrust($modern, $password);
        } catch (Exception $e) {
            return null;
        }
    }
}
