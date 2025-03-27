<?php declare(strict_types=1);

namespace Gzp\WbsNg\Mapping\Exceptions;


class Required extends Invalid
{
    public function __construct()
    {
        parent::__construct('required but not set');
    }
}