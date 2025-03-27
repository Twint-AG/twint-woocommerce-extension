<?php declare(strict_types=1);

namespace WbsVendors\Dgm\WpAjaxApi;


/**
 * @psalm-immutable
 */
class Response
{
    public const OK = 200;
    public const BadRequest = 400;
    public const Forbidden = 403;
    public const MethodNotAllowed = 405;
    public const UnsupportedMediaType = 415;
    public const InternalServerError = 500;

    /**
     * @var int
     */
    public $code;

    /**
     * @var string
     */
    public $body;

    /**
     * @var ?string
     */
    public $contentType;


    public static function empty(int $code = self::OK): self
    {
        return new \WbsVendors\Dgm\WpAjaxApi\Response($code);
    }

    public static function text(string $body, int $code = self::OK): self
    {
        return new \WbsVendors\Dgm\WpAjaxApi\Response($code, 'text/plain', $body);
    }

    public static function json($body, int $code = self::OK, $dontEncodeBody = false): self
    {
        if (!$dontEncodeBody) {
            $body = json_encode($body);
        }
        return new \WbsVendors\Dgm\WpAjaxApi\Response($code, 'application/json', $body);
    }

    private function __construct(int $code, string $contentType = null, string $body = '')
    {
        $this->code = $code;
        $this->contentType = $contentType;
        $this->body = $body;
    }
}