<?php

namespace TWINT\Views;

use TWINT\Includes\ServiceProvider\AbstractServiceProvider;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigTemplateEngine extends AbstractServiceProvider
{
    private $loader;
    private $twigInstance;
    private string $viewPath;

    public function __construct($args = [])
    {
        parent::__construct($args);
        $this->viewPath = dirname(__FILE__) . '/Templates';
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