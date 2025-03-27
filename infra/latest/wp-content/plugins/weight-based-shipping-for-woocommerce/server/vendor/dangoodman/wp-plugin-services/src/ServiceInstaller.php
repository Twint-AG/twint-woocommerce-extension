<?php
namespace WbsVendors\Dgm\PluginServices;

use LogicException;


class ServiceInstaller
{
    public static function create()
    {
        return new static();
    }

    public function installIfReady(\WbsVendors\Dgm\PluginServices\IService $service /*, ...*/)
    {
        $services = func_get_args();

        foreach ($services as $service) {

            $serviceId = $this->serviceId($service);

            if (isset($this->services[$serviceId])) {
                throw new LogicException("Service #{$serviceId} is already installed.");
            }

            if ($service instanceof \WbsVendors\Dgm\PluginServices\IServiceReady) {
                if (!$service->ready()) {
                    continue;
                }
            }

            $service->install();

            $this->services[$serviceId] = $service;
        }
    }

    private $services = array();

    private function serviceId(\WbsVendors\Dgm\PluginServices\IService $service)
    {
        return get_class($service);
    }
}