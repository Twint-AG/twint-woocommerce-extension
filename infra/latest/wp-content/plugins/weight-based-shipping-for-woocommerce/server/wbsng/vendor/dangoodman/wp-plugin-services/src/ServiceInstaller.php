<?php
namespace GzpWbsNgVendors\Dgm\PluginServices;

use LogicException;


class ServiceInstaller
{
    public static function create()
    {
        return new static();
    }

    public function installIfReady(IService $service /*, ...*/)
    {
        $services = func_get_args();

        foreach ($services as $service) {

            $serviceId = $this->serviceId($service);

            if (isset($this->services[$serviceId])) {
                throw new LogicException("Service #{$serviceId} is already installed.");
            }

            if ($service instanceof IServiceReady) {
                if (!$service->ready()) {
                    continue;
                }
            }

            $service->install();

            $this->services[$serviceId] = $service;
        }
    }

    private $services = array();

    private function serviceId(IService $service)
    {
        return get_class($service);
    }
}