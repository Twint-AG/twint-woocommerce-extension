<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Modal;

use Twint\Woo\Plugin;
use Twint\Woo\Service\AppsService;

class Modal
{
    private bool $registered = false;

    private array $links = [];

    private bool $isAndroid = false;

    private bool $isIos = false;

    private bool $isMobile = false;

    public function __construct(
        private readonly AppsService $service
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

        $modal = $this;

        require Plugin::abspath() . 'src/View/Frontend/modal.php';
    }

    protected function getVariables(): void
    {
        $links = $this->service->getPayLinks();

        $this->links = $links;

        $this->isAndroid = isset($links['android']);
        $this->isIos = isset($links['ios']);

        $this->isMobile = $this->isAndroid || $this->isIos;
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
                $app .= '<img src="' . esc_url(Plugin::assets("/images/{$icon}.png")) . '" 
                    class="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
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
                <div class="my-6 text-center">
                    ' . __('Choose your TWINT app:', 'twint-woocommerce-extension') . '
                </div>
    
                <div class="twint-app-container w-3/4 mx-auto justify-center max-w-screen-md mx-auto grid grid-cols-3 gap-4">
                    ' . $app . '
                </div>
                
                <select class="twint-select">
                    <option>' . __('Other banks', 'twint-woocommerce-extension') . '</option>
                    ' . $else . '
                </select>    
            </div>        
        ';
    }
}
