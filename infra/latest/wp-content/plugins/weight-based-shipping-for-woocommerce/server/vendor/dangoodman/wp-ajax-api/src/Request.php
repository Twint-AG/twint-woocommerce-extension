<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi;


use Dgm\WpAjaxApi\Internal\ResponseException;


class Request
{
    /**
     * @psalm-readonly
     * @var array<string, string>
     */
    public $query;


    public static function fromEnv(): self
    {
        return new self();
    }

    public function __construct()
    {
        $this->contentType = $_SERVER["CONTENT_TYPE"];
        $this->query = $_GET;
    }

    /**
     * @return mixed
     * @throws ResponseException
     */
    public function json()
    {
        if ($this->contentType !== 'application/json') {
            throw new \WbsVendors\Dgm\WpAjaxApi\Internal\ResponseException(\WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::UnsupportedMediaType));
        }

        $requestBody = file_get_contents('php://input');
        if (!$requestBody) {
            throw new \WbsVendors\Dgm\WpAjaxApi\Internal\ResponseException(\WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::InternalServerError));
        }

        $requestBody = json_decode($requestBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \WbsVendors\Dgm\WpAjaxApi\Internal\ResponseException(\WbsVendors\Dgm\WpAjaxApi\Response::empty(\WbsVendors\Dgm\WpAjaxApi\Response::BadRequest));
        }

        return $requestBody;
    }

    /**
     * @var ?string
     */
    private $contentType;
}