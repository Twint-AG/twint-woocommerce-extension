<?php
namespace WbsVendors\Deferred;

/**
 * Defers function call until this object destruction.
 *
 * Can be used for "finally" reserved word emulation.
 */
class Deferred
{
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function __destruct()
    {
        $callback = $this->callback;
        if (!isset($callback))
        {
            return;
        }

        $this->callback = null;

        $callback();
    }


    private $callback;
}