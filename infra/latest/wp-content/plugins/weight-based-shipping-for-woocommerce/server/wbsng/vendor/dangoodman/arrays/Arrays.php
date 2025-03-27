<?php
namespace GzpWbsNgVendors\Dgm\Arrays;

use Traversable;


class Arrays
{
    /**
     * Built-in array_map() emits a ridiculous 'An error occurred while invoking the map callback' warning
     * if map callback throws an exception. This custom implementation fixes this. It also changes arguments
     * order to make them consistent with other functions.
     *
     * @param array|Traversable $list
     * @param callable $callback
     * @return array
     */
    public static function map($list, $callback)
    {
        $result = is_array($list) ? $list : array();
        foreach ($list as $key => $item) {
            $result[$key] = call_user_func($callback, $item);
        }

        return $result;
    }

    /**
     * Built-in array_reduce() emits a ridiculous 'An error occurred while invoking the reduction callback' warning
     * if reduce callback throws an exception. This custom implementation fixes this.
     *
     * @param array|Traversable $input
     * @param callable $callback
     * @param mixed $carry
     * @return array
     */
    public static function reduce($input, $callback, $carry = null)
    {
        foreach ($input as $item) {
            $carry = call_user_func($callback, $carry, $item);
        }

        return $carry;
    }

    /**
     * Built-in array_filter() emits a ridiculous 'An error occurred while invoking the filter callback' warning
     * if filter callback throws an exception. This custom implementation fixes this.
     *
     * @param array|Traversable $input
     * @param callable $callback
     * @return array
     */
    public static function filter($input, $callback = null)
    {
        if (!isset($callback)) {
            $callback = function($item) {
                return !empty($item);
            };
        }

        $result = is_array($input) ? $input : array();
        foreach ($input as $key => $item) {
            if (call_user_func($callback, $item)) {
                $result[$key] = $item;
            } else {
                unset($result[$key]);
            }
        }

        return $result;
    }
}