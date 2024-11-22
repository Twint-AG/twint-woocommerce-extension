<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Modal;

use Twint\Woo\Plugin;

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
        require Plugin::abspath() . 'src/View/Frontend/loading.php';
    }
}
