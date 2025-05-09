<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/src/Command/CliCommand.php',
    ]);

    $rectorConfig->rules([
        TypedPropertyFromStrictConstructorRector::class,
    ]);

    // Define prepared sets of rules for dead code and code quality
    $rectorConfig->sets([
        \Rector\Set\ValueObject\SetList::DEAD_CODE,
        \Rector\Set\ValueObject\SetList::CODE_QUALITY,
    ]);
};
