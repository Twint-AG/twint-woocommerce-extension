<?php declare(strict_types=1);

namespace GzpWbsNgVendors\Dgm\WpAjaxApi;


use GzpWbsNgVendors\Dgm\WpAjaxApi\Internal\ResponseException;


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
            throw new ResponseException(Response::empty(Response::UnsupportedMediaType));
        }

        $requestBody = file_get_contents('php://input');
        if (!$requestBody) {
            throw new ResponseException(Response::empty(Response::InternalServerError));
        }

        $requestBody = json_decode($requestBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ResponseException(Response::empty(Response::BadRequest));
        }

        return $requestBody;
    }

    /**
     * @var ?string
     */
    private $contentType;
}