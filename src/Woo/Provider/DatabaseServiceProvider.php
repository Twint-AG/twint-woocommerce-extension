<?php

namespace Twint\Woo\Provider;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    const DATABASE_SERVICE_PROVIDER = 'databaseServiceProvider';
    const DATABASE_TRIGGER_STATE_ERROR_CODE = 45000;

    public function __construct($args = [])
    {
        parent::__construct($args);
    }

    public static function GET_INSTANCE()
    {
        $instance = self::getServiceByName(self::DATABASE_SERVICE_PROVIDER);

        if ($instance === null) {
            $instance = self::INSTANCE();
        }

        return $instance;
    }

    public static function INSTANCE($args = []): DatabaseServiceProvider
    {
        return (new self($args))
            ->boot()
            ->load();
    }

    protected function load(): DatabaseServiceProvider
    {
        $this->registerToServiceContainer(self::DATABASE_SERVICE_PROVIDER, $this);
        return $this;
    }

    protected function boot(): DatabaseServiceProvider
    {
        return $this;
    }
}
