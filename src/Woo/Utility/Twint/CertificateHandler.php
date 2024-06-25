<?php

declare(strict_types=1);

namespace Twint\Woo\Utility\Twint;

use Exception;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Exception\InvalidCertificate;
use Twint\Sdk\Io\InMemoryStream;

class CertificateHandler
{
    public const ERROR_INVALID_UNKNOWN = 'ERROR_INVALID_UNKNOWN';

    public const ERROR_INVALID_INPUT = 'ERROR_INVALID_INPUT';

    public function read(string $pkcs12, string $password): Pkcs12Certificate|string
    {
        try {
            if ($pkcs12 === '' || $password === '') {
                return self::ERROR_INVALID_INPUT;
            }

            return Pkcs12Certificate::establishTrust(new InMemoryStream($pkcs12), $password, new Clock());
        } catch (InvalidCertificate $e) {
            $codes = CertificateErrorMapper::getErrorCodes();
            foreach ($e->getErrors() as $error) {
                return $codes[$error] ?? static::ERROR_INVALID_UNKNOWN;
            }
        } catch (Exception $e) {
            return self::ERROR_INVALID_UNKNOWN;
        }
    }
}
