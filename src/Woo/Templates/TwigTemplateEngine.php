<?php

namespace Twint\Woo\Templates;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twint\Woo\Includes\ServiceProvider\AbstractServiceProvider;
use WC_Twint_Payments;

class TwigTemplateEngine extends AbstractServiceProvider
{
    private $loader;
    private $twigInstance;
    private string $viewPath;

    public function __construct($args = [])
    {
        parent::__construct($args);
        $this->viewPath = WC_Twint_Payments::plugin_abspath() . '/src/Woo/Templates';
    }

    protected function boot(): void
    {
        /**
         * Register some further things if needed later on
         */
    }

    protected function load(): void
    {
        $this->loader = new FilesystemLoader($this->viewPath);
        $this->twigInstance = new Environment($this->loader, [
//            'cache' => '/path/to/compilation_cache',
        ]);
    }

    public static function INSTANCE($args = []): Environment
    {
        $instance = new self($args);
        $instance->boot();
        $instance->load();
        return $instance->twigInstance;
    }
}