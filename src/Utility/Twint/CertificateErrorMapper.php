<?php

declare(strict_types=1);

namespace TWINT\Utility\Twint;

use ReflectionClass;
use Twint\Sdk\Exception\InvalidCertificate;

class CertificateErrorMapper
{
    /**
     * Get all error possible error codes
     */
    public static function getErrorCodes(): array
    {
        $reflection = new ReflectionClass(InvalidCertificate::class);
        $constants = $reflection->getConstants();

        return array_flip(array_filter(
            $constants,
            static function ($value, $name) use ($reflection) {
                $constant = $reflection->getReflectionConstant($name);
                return $constant && $constant->isPublic();
            },
            ARRAY_FILTER_USE_BOTH
        ));
    }
}
