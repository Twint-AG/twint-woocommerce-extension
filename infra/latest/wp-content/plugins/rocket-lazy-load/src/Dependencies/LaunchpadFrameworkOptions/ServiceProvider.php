<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions;


use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container\AbstractServiceProvider;
use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container\HasInflectorInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadCore\Container\InflectorServiceProviderTrait;
use RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions\Interfaces\OptionsAwareInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions\Interfaces\SettingsAwareInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions\Interfaces\TransientsAwareInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\OptionsInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\SettingsInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\TransientsInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Options;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Settings;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Transients;
use RocketLazyLoadPlugin\Dependencies\League\Container\Definition\DefinitionInterface;

class ServiceProvider extends AbstractServiceProvider implements HasInflectorInterface
{
    use InflectorServiceProviderTrait;

    protected function define()
    {
        $this->register_service(OptionsInterface::class)
            ->share()
            ->set_concrete(Options::class)
            ->set_definition(function (DefinitionInterface $definition) {
            $definition->addArgument('prefix');
        });

        $this->register_service(TransientsInterface::class)
            ->share()
            ->set_concrete(Transients::class)
            ->set_definition(function (DefinitionInterface $definition) {
            $definition->addArgument('prefix');
        });

        $this->register_service(SettingsInterface::class)
            ->share()
            ->set_concrete(Settings::class)
            ->set_definition(function (DefinitionInterface $definition) {
            $prefix = $this->container->get('prefix');
            $definition->addArguments([OptionsInterface::class, "{$prefix}settings"]);
        });
    }

    /**
     * Returns inflectors.
     *
     * @return array[]
     */
    public function get_inflectors(): array
    {
        return [
            OptionsAwareInterface::class => [
                'method' => 'set_options',
                'args' => [
                    OptionsInterface::class,
                ],
            ],
            TransientsAwareInterface::class => [
                'method' => 'set_transients',
                'args' => [
                    TransientsInterface::class,
                ],
            ],
            SettingsAwareInterface::class => [
                'method' => 'set_settings',
                'args' => [
                    SettingsInterface::class,
                ],
            ],
        ];
    }
}