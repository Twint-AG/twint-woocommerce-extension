<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi;


use Dgm\WpAjaxApi\Internal\EndpointGateway;


class WpAjaxApi
{
    public const NextUrlHeader = 'X-Dgm-WpAjaxApi-NextUrl';

    public function install(): void
    {
        $doingAjax = defined('DOING_AJAX') && DOING_AJAX && is_admin();
        if (!$doingAjax) {
            return;
        }

        foreach ($this->endpoints as $e) {
            $e->install();
        }
    }

    public function register(\WbsVendors\Dgm\WpAjaxApi\Endpoint $endpoint): \WbsVendors\Dgm\WpAjaxApi\RegisteredEndpoint
    {
        $gw = new \WbsVendors\Dgm\WpAjaxApi\Internal\EndpointGateway(new \WbsVendors\Dgm\WpAjaxApi\Internal\NormalizedEndpoint($endpoint));
        $this->endpoints[] = $gw;
        return $gw;
    }

    /**
     * @var array<EndpointGateway>
     */
    private $endpoints = [];
}