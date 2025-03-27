<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);

namespace Wbs;

use WbsVendors\Dgm\WcTools\WcTools;
use WbsVendors\Dgm\WpAjaxApi\Endpoint;
use WbsVendors\Dgm\WpAjaxApi\RegisteredEndpoint;
use WbsVendors\Dgm\WpAjaxApi\Request;
use WbsVendors\Dgm\WpAjaxApi\Response;
use WbsVendors\Dgm\WpAjaxApi\WpAjaxApi;


class Api
{
    /**
     * @readonly
     * @var RegisteredEndpoint
     */
    public static $config;

    /**
     * @readonly
     * @var RegisteredEndpoint
     */
    public static $globalSwitch;


    public static function init(): void
    {
        $wpAjaxApi = new WpAjaxApi();
        self::$config = $wpAjaxApi->register(new ConfigEndpoint());
        self::$globalSwitch = $wpAjaxApi->register(new WbsNgGlobalSwitchEndpoint());
        $wpAjaxApi->install();
    }
}


/**
 * @internal
 */
class ConfigEndpoint extends Endpoint
{
    public $permissions = ['manage_woocommerce'];
    public $urlParams = ['instance_id'];

    public function post(Request $request): Response
    {
        $data = $request->json();

        if (!array_key_exists('config', $data)) {
            return Response::empty(Response::BadRequest);
        }
        $config = $data['config'];

        $instanceId = @$request->query['instance_id'];

        $method = new ShippingMethod($instanceId);
        $method->config($config);

        return Response::empty();
    }
}


class WbsNgGlobalSwitchEndpoint extends Endpoint
{
    public $permissions = ['manage_woocommerce'];

    public function post(Request $request): Response
    {
        $value = (string)$request->json();
        if (!in_array($value, ['only-wbs', 'only-wbsng', 'both'], true)) {
            return Response::empty(Response::BadRequest);
        }

        update_option('wbs_global_methods', $value);
        WcTools::purgeShippingCache();

        return Response::empty();
    }
}