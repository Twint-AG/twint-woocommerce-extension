<?php
namespace WbsVendors\Dgm\Shengine\Migrations\Storage;

use Dgm\Shengine\Migrations\Interfaces\Storage\IStorage;


/**
 * Simple in-memory IStorage implementation supposed mainly for mocking.
 */
class ArrayStorage implements \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorage
{
    public function __construct(array $array = array())
    {
        $this->array = $array;
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->array) ? $this->array[$key] : $default;
    }

    public function set($key, $value, $autoload = null)
    {
        $this->array[$key] = $value;
    }

    public function bind($key, $default = null)
    {
        return new \WbsVendors\Dgm\Shengine\Migrations\Storage\StorageRecord($this, $key, $default);
    }

    public function findKeysLike($sqlLikePattern)
    {
        $regex = array();
        for ($i=0; $i<strlen($sqlLikePattern); $i++) {
            $c = $sqlLikePattern[$i];
            switch ($c) {
                case '\\':
                    $cn = $sqlLikePattern[$i+1];
                    if (in_array($cn, array('\\', '%', '_'))) {
                        $regex[] = preg_quote($cn, '/');
                        $i++;
                    }
                    break;
                case '%':
                    $regex[] = ".*";
                    break;
                case '_':
                    $regex[] = '.';
                    break;
                default:
                    $regex[] = preg_quote($c, '/');
            }
        }

        $regex = '/^'.join('', $regex).'$/i';

        $matchingKeys = array_values(array_filter(array_keys($this->array), function($k) use($regex) {
            return preg_match($regex, $k);
        }));

        return $matchingKeys;
    }

    public function escapeForLikePattern($string)
    {
        return addcslashes($string, '_%\\');
    }


    public $array = array();
}