<?php declare(strict_types=1);

namespace GzpWbsNgVendors\Dgm\WpAjaxApi\Internal;

use GzpWbsNgVendors\Dgm\WpAjaxApi\Endpoint;
use GzpWbsNgVendors\Dgm\WpAjaxApi\Request;
use GzpWbsNgVendors\Dgm\WpAjaxApi\Response;


/**
 * @internal
 */
class NormalizedEndpoint extends Endpoint
{
    public function __construct(Endpoint $endpoint)
    {
        $this->id = $endpoint->id ?? strtolower(str_replace('\\', '_', get_class($endpoint)));

        if (!isset($endpoint->permissions)) {
            throw new \LogicException("endpoint permissions are not provided");
        }
        $this->permissions = $endpoint->permissions;

        $this->urlParams = $endpoint->urlParams;

        $this->endpoint = $endpoint;
    }

    public function get(Request $request): Response
    {
        return $this->handleExceptions(function() use ($request) {
            return $this->endpoint->get($request);
        });
    }

    public function post(Request $request): Response
    {
        return $this->handleExceptions(function() use ($request) {
            return $this->endpoint->post($request);
        });
    }

    /**
     * @var Endpoint
     */
    private $endpoint;

    private function handleExceptions(callable $handler): Response
    {
        try {
            return $handler();
        } catch (ResponseException $e) {
            return $e->response;
        } catch (\Exception $e) {
            return Response::empty(500);
        }
    }
}