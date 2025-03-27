<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi\Internal;

use Dgm\WpAjaxApi\Endpoint;
use Dgm\WpAjaxApi\Request;
use Dgm\WpAjaxApi\Response;


/**
 * @internal
 */
class NormalizedEndpoint extends \WbsVendors\Dgm\WpAjaxApi\Endpoint
{
    public function __construct(\WbsVendors\Dgm\WpAjaxApi\Endpoint $endpoint)
    {
        $this->id = $endpoint->id ?? strtolower(str_replace('\\', '_', get_class($endpoint)));

        if (!isset($endpoint->permissions)) {
            throw new \LogicException("endpoint permissions are not provided");
        }
        $this->permissions = $endpoint->permissions;

        $this->urlParams = $endpoint->urlParams;

        $this->endpoint = $endpoint;
    }

    public function get(\WbsVendors\Dgm\WpAjaxApi\Request $request): \WbsVendors\Dgm\WpAjaxApi\Response
    {
        return $this->handleExceptions(function() use ($request) {
            return $this->endpoint->get($request);
        });
    }

    public function post(\WbsVendors\Dgm\WpAjaxApi\Request $request): \WbsVendors\Dgm\WpAjaxApi\Response
    {
        return $this->handleExceptions(function() use ($request) {
            return $this->endpoint->post($request);
        });
    }

    /**
     * @var Endpoint
     */
    private $endpoint;

    private function handleExceptions(callable $handler): \WbsVendors\Dgm\WpAjaxApi\Response
    {
        try {
            return $handler();
        } catch (ResponseException $e) {
            return $e->response;
        } catch (\Exception $e) {
            return \WbsVendors\Dgm\WpAjaxApi\Response::empty(500);
        }
    }
}