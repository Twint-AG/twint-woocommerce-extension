<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi;


abstract class Endpoint
{
    /**
     * A string to identify the endpoint globally(!).
     * If null, WpAjaxApi will use the full class name.
     *
     * @psalm-readonly
     * @var ?string
     */
    public $id;

    /**
     * Permissions required for any endpoint method.
     * Null permissions are not allowed. Set to an empty array if the endpoint should really be available to anyone.
     *
     * @psalm-readonly
     * @var array<string>
     */
    public $permissions;

    /**
     * @psalm-readonly
     * @var array<string>
     */
    public $urlParams = [];


    /**
     * A GET handler must not change anything in the system.
     * The gateway checks the permissions, but ignores the nonce.
     *
     * A handler may throw an exception. It will be caught and converted to a 500 HTTP error.
     */
    public function get(\WbsVendors\Dgm\WpAjaxApi\Request $request): \WbsVendors\Dgm\WpAjaxApi\Response
    {
        return \WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::MethodNotAllowed);
    }

    /**
     * A POST handler may do anything.
     * The gateway checks both the permissions and the nonce.
     *
     * A handler may throw an exception. It will be caught and converted to a 500 HTTP error.
     */
    public function post(\WbsVendors\Dgm\WpAjaxApi\Request $request): \WbsVendors\Dgm\WpAjaxApi\Response
    {
        return \WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::MethodNotAllowed);
    }
}