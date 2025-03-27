<?php declare(strict_types=1);

namespace GzpWbsNgVendors\Dgm\WpAjaxApi;


interface RegisteredEndpoint
{
    public function url(array $params = []);
}