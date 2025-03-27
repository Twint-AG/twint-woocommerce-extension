<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi\Internal;

use Dgm\WpAjaxApi\RegisteredEndpoint;
use Dgm\WpAjaxApi\Request;
use Dgm\WpAjaxApi\Response;
use Dgm\WpAjaxApi\WpAjaxApi;


/**
 * EndpointGateway is not a RegisteredEndpoint idiomatically. But that `implements` clause saves a ton of boilerplate
 * required to create a RegisteredEndpoint implementation using the gateway methods; due to the lack of function
 * signatures and a limited support for anonymous classes in PHP.
 *
 * @internal
 */
class EndpointGateway implements \WbsVendors\Dgm\WpAjaxApi\RegisteredEndpoint
{
    public function __construct(\WbsVendors\Dgm\WpAjaxApi\Internal\NormalizedEndpoint $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->nonce = new \WbsVendors\Dgm\WpAjaxApi\Internal\Nonce($endpoint->id);
    }

    public function install(): void
    {
        add_action("wp_ajax_{$this->wpAjaxEndpointId()}", function() { $this->handle(); });
    }

    /**
     * @return never
     */
    private function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method ?? null, ['GET', 'POST'], true)) {
            self::die(\WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::MethodNotAllowed));
        }

        foreach ($this->endpoint->permissions as $p) {
            if (!current_user_can($p)) {
                self::die(\WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::Forbidden));
            }
        }

        /**
         * Nonce is ignored for GET requests. Assuming GET requests never change anything.
         */
        if ($method === 'POST') {

            $params = array_intersect_key($_GET, array_flip($this->endpoint->urlParams));
            header(join(': ', [\WbsVendors\Dgm\WpAjaxApi\WpAjaxApi::NextUrlHeader, $this->url($params)]));

            if (!$this->nonce->valid()) {
                self::die(\WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::Forbidden));
            }
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        $response = null;
        $request = \WbsVendors\Dgm\WpAjaxApi\Request::fromEnv();
        switch ($method) {
            case 'GET':
                $response = $this->endpoint->get($request);
                break;
            case 'POST':
                $response = $this->endpoint->post($request);
                break;
            default:
                throw new \RuntimeException("unexpected method $method");
        }

        self::die($response);
    }

    public function url(array $params = []): string
    {
        $allowedParams = $this->endpoint->urlParams;
        if (!empty($unknownParams = array_diff(array_keys($params), $allowedParams))) {
            throw new \LogicException('unknown url params: ' . join(', ', $unknownParams));
        }

        $params = array_filter($params, function($x) { return isset($x); });

        $params = $this->nonce->addToArgs($params);
        $params['action'] = $this->wpAjaxEndpointId();

        $query = http_build_query($params, '', '&');

        return admin_url("admin-ajax.php?$query");
    }

    /**
     * @psalm-readonly
     * @var NormalizedEndpoint
     */
    private $endpoint;

    /**
     * @psalm-readonly
     * @var Nonce
     */
    private $nonce;


    private function wpAjaxEndpointId(): string
    {
        return $this->endpoint->id;
    }

    /**
     * @return never
     */
    private static function die(\WbsVendors\Dgm\WpAjaxApi\Response $response): void
    {
        if (isset($response->contentType)) {
            header('Content-Type: ' . $response->contentType);
        }

        /** @noinspection ForgottenDebugOutputInspection */
        wp_die($response->body, $response->code);
    }
}