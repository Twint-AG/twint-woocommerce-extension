<?php
namespace WbsVendors\Dgm\ComposerCapsule\Runtime;


class Runtime
{
    public function __construct(\WbsVendors\Dgm\ComposerCapsule\Runtime\Wrapper $wrapper)
    {
        $this->wrapper = $wrapper;
    }

    public function spl_autoload_register($autoloader = null, $throw = true, $prepend = false)
    {
        $args = func_get_args();

        $originalCallableId = null;

        if ($args) {

            $callable = $args[0];
            $originalCallableId = self::getCallableId($callable);

            if (isset($this->autoloaders[$originalCallableId])) {
                $callable = $this->autoloaders[$originalCallableId];

            } else {
                $wrapper = $this->wrapper;
                $callable = function () use ($callable, $wrapper) {

                    $args = func_get_args();

                    if ($args) {
                        $wrapper->unwrap($args[0], \WbsVendors\Dgm\ComposerCapsule\Runtime\Wrapper::REFKIND_CLASS);
                    }

                    $result = call_user_func_array($callable, $args);

                    return $result;
                };
            }

            $args[0] = $callable;
        }

        $result = call_user_func_array('spl_autoload_register', $args);

        if ($result && isset($originalCallableId)) {
            $this->autoloaders[$originalCallableId] = $args[0];
        }

        return $result;
    }

    public function spl_autoload_unregister($autoload_function)
    {
        $args = func_get_args();

        $originalCallableId = null;

        if ($args) {

            $callable = $args[0];
            $originalCallableId = self::getCallableId($callable);

            if (isset($this->autoloaders[$originalCallableId])) {
                $callable = $this->autoloaders[$originalCallableId];
            }

            $args[0] = $callable;
        }

        $result = call_user_func_array('spl_autoload_unregister', $args);

        if ($result && isset($originalCallableId)) {
            unset($this->autoloaders[$originalCallableId]);
        }

        return $result;
    }

    public function kallable($callable)
    {
        $tmp = $callable;

        if (is_string($tmp)) {
            if (strpos($tmp, '::') === false) {
                if ($this->wrapper->wrap($tmp, \WbsVendors\Dgm\ComposerCapsule\Runtime\Wrapper::REFKIND_FUNC)) {
                    $callable = $tmp;
                }
            } else {
                $tmp = explode('::', $tmp, 3);
                if ($this->wrapArrayCallable($tmp)) {
                    $callable = $tmp;
                }
            }
        } elseif ($this->wrapArrayCallable($tmp)) {
            $callable = $tmp;
        }

        return $callable;
    }

    public function klass($class)
    {
        if (is_string($class)) {
            $this->wrapper->wrap($class, \WbsVendors\Dgm\ComposerCapsule\Runtime\Wrapper::REFKIND_CLASS);
        }

        return $class;
    }

    private $wrapper;
    private $autoloaders = array();

    private function wrapArrayCallable(&$callable)
    {
        if (is_array($callable) && count($callable) == 2) {

            $k = key($callable);
            $class = &$callable[$k];
            $originalClass = $class;

            $class = $this->klass($originalClass);

            return $class !== $originalClass;
        }

        return false;
    }

    static private function normalizeCallable($callable)
    {
        if (is_string($callable)) {
            if (count($tmp = explode('::', $callable, 2)) > 1) {
                $callable = $tmp;
            }
        }

        return $callable;
    }

    static private function getCallableId($callable)
    {
        $callable = self::normalizeCallable($callable);

        if (!is_array($callable)) {
            $callable = array($callable);
        }

        $callable = array_map(function($part) {
            return is_object($part) ? '#'.spl_object_hash($part) : (string)$part;
        }, $callable);

        $callable = join('/', $callable);

        return $callable;
    }
}