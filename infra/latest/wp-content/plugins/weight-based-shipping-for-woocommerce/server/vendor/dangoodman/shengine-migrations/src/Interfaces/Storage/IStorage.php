<?php
namespace WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage;


interface IStorage extends \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorageAccess
{
    /**
     * @param string $key
     * @param mixed $default
     * @return IStorageRecord
     */
    function bind($key, $default = null);

    /**
     * @param string $sqlLikePatterns  A pattern formed for SQL LIKE operator. Don't forget to escape special
     *                                 chars such as '%', '\' and '_' with {@see IStorageDriver::escapeForLike()}.
     * @return string[]|iterable  A list of keys matching the pattern.
     */
    function findKeysLike($sqlLikePatterns);

    /**
     * @param string $string
     * @return string
     */
    function escapeForLikePattern($string);
}