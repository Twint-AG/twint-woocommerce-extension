<?php declare(strict_types=1);

namespace GzpWbsNgVendors\Dgm\WpAjaxApi\Internal;


/**
 * @internal
 */
class Nonce
{
    public function __construct(string $action)
    {
        $this->action = $action;
    }

    public function valid(): bool
    {
        return check_ajax_referer($this->action, self::NonceArg, false) !== false;
    }

    public function addToArgs(array $args = []): array
    {
        $args[self::NonceArg] = wp_create_nonce($this->action);
        return $args;
    }


    /**
     * @psalm-readonly
     * @var string
     */
    private $action;

    private const NonceArg = '_wpnonce';
}