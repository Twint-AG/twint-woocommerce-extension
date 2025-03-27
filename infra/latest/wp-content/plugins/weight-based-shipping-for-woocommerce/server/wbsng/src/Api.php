<?php declare(strict_types=1);
namespace Gzp\WbsNg;

use GzpWbsNgVendors\Dgm\WpAjaxApi\Endpoint;
use GzpWbsNgVendors\Dgm\WpAjaxApi\RegisteredEndpoint;
use GzpWbsNgVendors\Dgm\WpAjaxApi\Request;
use GzpWbsNgVendors\Dgm\WpAjaxApi\Response;
use GzpWbsNgVendors\Dgm\WpAjaxApi\WpAjaxApi;


class Api
{
    public const NextUrlHeader = WpAjaxApi::NextUrlHeader;

    public static function init(): void
    {
        $wpAjaxApi = new WpAjaxApi();
        self::$config = $wpAjaxApi->register(new ConfigEndpoint());
        $wpAjaxApi->install();
    }

    public static function configEndpointUrl(int $instanceId = null): string
    {
        return self::$config->url([ConfigEndpoint::InstanceIdArg => $instanceId]);
    }

    /**
     * @var RegisteredEndpoint
     */
    private static $config;
}


/**
 * @internal
 */
class ConfigEndpoint extends Endpoint
{
    public const InstanceIdArg = 'instance_id';

    public $permissions = ['manage_woocommerce'];
    public $urlParams = [self::InstanceIdArg];


    public function get(Request $request): Response
    {
        $instanceId = @$request->query[self::InstanceIdArg];
        $method = new ShippingMethod($instanceId);
        return Response::json($method->configData());
    }

    public function post(Request $request): Response
    {
        $config = $request->json();

        $instanceId = @$request->query[self::InstanceIdArg];

        $method = new ShippingMethod($instanceId);

        try {
            $method->updateConfigData($config);
        }
        catch (ConfigError $e) {
            return Response::text($e->getMessage(), Response::BadRequest);
        }

        return Response::empty();
    }
}