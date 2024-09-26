<?php

declare(strict_types=1);

namespace Twint\Woo\Provider;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    public const DATABASE_SERVICE_PROVIDER = 'databaseServiceProvider';

    public const DATABASE_TRIGGER_STATE_ERROR_CODE = 45000;

    public function __construct()
    {
    }

    public static function GET_INSTANCE()
    {
        $instance = self::getServiceByName(self::DATABASE_SERVICE_PROVIDER);

        if ($instance === null) {
            $instance = self::INSTANCE();
        }

        return $instance;
    }

    public static function INSTANCE($args = []): self
    {
        return (new self($args))
            ->boot()
            ->load();
    }

    protected function load(): self
    {
        $this->registerToServiceContainer(self::DATABASE_SERVICE_PROVIDER, $this);
        return $this;
    }

    protected function boot(): self
    {
        return $this;
    }
}
