<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi;


interface RegisteredEndpoint
{
    public function url(array $params = []);
}