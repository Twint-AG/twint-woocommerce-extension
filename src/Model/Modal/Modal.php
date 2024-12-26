<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Modal;

use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Plugin;
use Twint\Woo\Service\AppsService;

/**
 * @method AppsService getService()
 */
class Modal
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['service'];

    private bool $registered = false;

    private array $links = [];

    private bool $isAndroid = false;

    private bool $isIos = false;

    private bool $isMobile = false;

    public function __construct(
        private Lazy|AppsService $service
    ) {
    }

    public function registerHooks(): void
    {
        if ($this->registered || is_admin()) {
            return;
        }

        add_action('wp_footer', [$this, 'render'], 98);

        add_action('wp_enqueue_scripts', static function () {
            Plugin::enqueueScript('clipboard-min', '/clipboard-min.js');
        });
    }

    public function render(): void
    {
        $this->getVariables();

        require Plugin::abspath() . 'src/View/Frontend/modal.php';
    }

    protected function getVariables(): void
    {
        $links = $this->getService()->getPayLinks();

        $this->links = $links;

        $this->isAndroid = isset($links['android']);
        $this->isIos = isset($links['ios']);

        $this->isMobile = $this->isAndroid || $this->isIos;
    }

    public function getMdClasses(string $classes): string
    {
        return $this->isMobile ? '' : $classes;
    }

    protected function getMobileClass(): string
    {
        return $this->isMobile ? ' twint-mobile ' : '';
    }

    protected function getIosHtml(): string
    {
        if (!$this->isIos) {
            return '';
        }

        $links = $this->links['ios'];

        $refinedApps = [
            'UBS TWINT' => 'bank-ubs',
            'Raiffeisen TWINT' => 'bank-raiffeisen',
            'PostFinance TWINT' => 'bank-pf',
            'ZKB TWINT' => 'bank-zkb',
            'Credit Suisse TWINT' => 'bank-cs',
            'BCV TWINT' => 'bank-bcv',
        ];

        $app = '';
        $else = '';

        foreach ($links as $link) {
            $icon = $refinedApps[$link['name']] ?? null;
            if ($icon) {
                //phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                $app .= '<img src="' . esc_url(Plugin::assets("/images/{$icon}.png")) . '" 
                    class="tw-shadow-2xl tw-w-64 tw-h-64 tw-rounded-2xl tw-mx-auto"
                    data-link="' . htmlentities($link['link']) . '"
                    alt="' . htmlentities($link['name']) . '">';
            } else {
                $else .= '<option value="' . htmlentities($link['link']) . '">' . htmlentities(
                    $link['name']
                ) . '</option>';
            }
        }

        return '
            <div id="twint-ios-container">
                <div class="tw-my-6 tw-text-center">
                    ' . esc_html__('Choose your TWINT app:', 'twint-woocommerce-extension') . '
                </div>
    
                <div class="twint-app-container tw-w-3/4 tw-mx-auto tw-justify-center max-tw-w-screen-md tw-mx-auto tw-grid tw-grid-cols-3 tw-gap-4">
                    ' . $app . '
                </div>
                
                <select class="twint-select">
                    <option>' . esc_html__('Other banks', 'twint-woocommerce-extension') . '</option>
                    ' . $else . '
                </select>    
            </div>        
        ';
    }
}
