<?php declare(strict_types=1);

namespace GzpWbsNgVendors\Dgm\WpAjaxApi\Internal;

use GzpWbsNgVendors\Dgm\WpAjaxApi\Response;


/**
 * Used to abort user code from library code, {@see Request::json()}.
 *
 * @psalm-immutable
 * @internal
 */
class ResponseException extends \RuntimeException
{
    /**
     * @var Response
     */
    public $response;


    public function __construct(Response $response, \Throwable $previous = null)
    {
        parent::__construct($response->body, $response->code, $previous);
        $this->response = $response;
    }
}