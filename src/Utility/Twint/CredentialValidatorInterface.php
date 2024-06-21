<?php

declare(strict_types=1);

namespace TWINT\Utility\Twint;

interface CredentialValidatorInterface
{
    public function validate(array $certificate, string $merchantId, bool $testMode): bool;
}
