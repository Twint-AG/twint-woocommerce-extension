<?php
namespace Wbs\Common;


class Once
{
    /**
     * @param callable $callback  function(): mixed
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @internal
     */
    function __invoke()
    {
        if (!$this->called) {
            $this->result = call_user_func($this->callback);
            $this->called = true;
        }

        return $this->result;
    }

    private $callback;
    private $result;
    private $called = false;
}

