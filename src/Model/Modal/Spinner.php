<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Modal;

class Spinner
{
    private bool $registered = false;

    public function registerHooks(): void
    {
        if (!$this->registered) {
            add_action('wp_footer', [$this, 'render'], 99);
            $this->registered = true;
        }
    }

    public function render(): void
    {
        echo $this->getContent();
    }

    private function getContent(): string
    {
        return '
            <div id="twint-loading">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" shape-rendering="geometricPrecision" text-rendering="geometricPrecision">
                    <g transform="matrix(.72509 0 0-.72509 12.626655 199.33733)">
                        <path d="M125.9,268.725c0,0,105.6-61,105.6-61c3-1.7,5.4-5.9,5.4-9.4c0,0,0-122,0-122c0-3.4-2.4-7.7-5.4-9.4c0,0-105.6-61-105.6-61-3-1.7-7.9-1.7-10.9,0c0,0-105.6,61-105.6,61-3,1.7-5.4,6-5.4,9.4c0,0,0,122,0,122c0,3.4,2.4,7.7,5.4,9.4c0,0,105.6,61,105.6,61c3,1.7,7.9,1.7,10.9,0Z"
                              paint-order="stroke fill markers" fill="none" fill-rule="evenodd" stroke="#dfdfdf" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <g transform="matrix(.72509 0 0-.72509 12.626655 199.33733)">
                        <path id="twint-animation" stroke-linecap="round" stroke-linejoin="round" fill-opacity="0" stroke="rgb(38,38,38)" stroke-opacity="1" stroke-width="10" d="M0 0"></path>
                    </g>
                </svg>
            </div>';
    }
}
