<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi\Internal;

use Dgm\WpAjaxApi\Response;


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


    public function __construct(\WbsVendors\Dgm\WpAjaxApi\Response $response, \Throwable $previous = null)
    {
        parent::__construct($response->body, $response->code, $previous);
        $this->response = $response;
    }
}