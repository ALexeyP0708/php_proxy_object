<?php


namespace Alpa\ProxyObject\Handlers;


use Alpa\ProxyObject\Proxy;

trait TStaticMethods
{
    public static function static_run(string $action, object $target, ?string $prop = null, $value_or_args = null, Proxy $proxy)
    {
        if (!in_array($action, ['get', 'set', 'isset', 'unset', 'call', 'iterator'])) {
            throw new \Exception('Action must be one of the values "get|set|isset|unset|call|iterator"');
        }
        $method = 'static_' . $action;
        $methodProp = null;
        if ($method !== 'iterator') {
            $methodProp = $method . '_' . $prop;
        }
        if ($methodProp !== null && method_exists(static::class, $methodProp)) {
            $method = $methodProp;
        }
        return call_user_func([static::class, $method], $target, $prop, $value_or_args, $proxy);
    }

    public static function proxy($target, $handlers = null): Proxy
    {
        $handlers = $handlers !== null ? $handlers : static::class;
        return new Proxy($target, $handlers);
    }

    /**
     * member value query handler
     * @param object $target - observable object
     * @param string $prop - object member name
     * @param null $value_or_args - irrelevant
     * @param Proxy $proxy - the proxy object from which the method is called
     * @return mixed - it is necessary to return the result
     */
    protected static function static_get(object $target, string $prop, $value_or_args = null, Proxy $proxy)
    {
        return $target->$prop;
    }

    /**
     * member value entry handler
     * @param object $target - observable object
     * @param string $prop - object member name
     * @param mixed $value_or_args - value to assign
     * @param Proxy $proxy - the proxy object from which the method is called
     * @return void
     */
    protected static function static_set(object $target, string $prop, $value_or_args, Proxy $proxy): void
    {
        $target->$prop = $value_or_args;
    }

    /**
     * member delete handler
     * @param object $target - observable object
     * @param string $prop -  object member name
     * @param null $value_or_args -irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return void
     */
    protected static function static_unset(object $target, string $prop, $value_or_args = null, Proxy $proxy): void
    {
        unset($target->$prop);
    }

    /**
     * checking is  set member handler
     * @param object $target - observable object
     * @param string $prop - object member name
     * @param null $value_or_args - irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return bool
     */
    protected static function static_isset(object $target, string $prop, $value_or_args = null, Proxy $proxy): bool
    {
        return isset($target->$prop);
    }

    /**
     * Member call handler
     * @param object $target - observable object
     * @param string $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    protected static function static_call(object $target, string $prop, array $value_or_args = [], Proxy $proxy)
    {
        return $target->$prop(...$value_or_args);
    }

    /**
     * creates an iterator for foreach
     * @param object $target - observable object
     * @param null $prop - irrelevant
     * @param null $value_or_args -irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return \Traversable
     */
    protected static function static_iterator(object $target, $prop = null, $value_or_args = null, Proxy $proxy): \Traversable
    {
        if ($target instanceof \IteratorAggregate) {
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }
}