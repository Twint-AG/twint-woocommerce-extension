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

        $filter = new \Twig\TwigFilter('__trans', function ($string) {
            return esc_html(__($string, 'woocommerce-gateway-twint'));
        });

        $twintAssetFunc = new \Twig\TwigFunction('twint_asset', function ($path) {
            return twint_assets($path);
        });

        $twigJsonDecodeFunc = new \Twig\TwigFunction('twint_json_decode', function ($string) {
            return json_decode($string);
        });

        $twigXMLBeautyFunc = new \Twig\TwigFunction('twint_xml_beauty', function ($xml) {
            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = true;
            $dom->formatOutput = true;
            $dom->loadXML($xml);
            return $dom->saveXML();
        });

        $this->twigInstance->addFunction($twintAssetFunc);
        $this->twigInstance->addFunction($twigJsonDecodeFunc);
        $this->twigInstance->addFunction($twigXMLBeautyFunc);
        $this->twigInstance->addFilter($filter);
    }

    public static function INSTANCE($args = []): Environment
    {
        $instance = new self($args);
        $instance->boot();
        $instance->load();
        return $instance->twigInstance;
    }
}