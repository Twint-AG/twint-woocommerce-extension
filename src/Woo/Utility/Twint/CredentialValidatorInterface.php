<?php

declare(strict_types=1);

namespace Twint\Woo\Utility\Twint;

interface CredentialValidatorInterface
{
    public function validate(array $certificate, string $merchantId, bool $testMode): bool;
}
