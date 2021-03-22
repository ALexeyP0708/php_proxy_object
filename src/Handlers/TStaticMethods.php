<?php


namespace Alpa\ProxyObject\Handlers;


use Alpa\ProxyObject\Proxy;

trait TStaticMethods
{
    public static function static_run(string $action, $target, ?string $prop = null, $value_or_args = null, Proxy $proxy)
    {
        if (!in_array($action, ['get', 'set', 'isset', 'unset', 'call','invoke','toString','iterator'])) {
            throw new \Exception('Action must be one of the values "get|set|isset|unset|call|invoke|iterator"');
        }
        $method = static::getActionPrefix() . $action;
        $methodProp = null;
        if (!in_array($action,['iterator','invoke'])) {
            $methodProp = $method . '_' . $prop;
        }
        if ($methodProp !== null && method_exists(static::class, $methodProp)) {
            $method = $methodProp;
        }
        return static::{$method}($target, $prop, $value_or_args, $proxy);
    }
    protected  static function getActionPrefix(): string
    {
        return '';
    }
    public static function proxy($target, $handlers = null): Proxy
    {
        $handlers = $handlers !== null ? $handlers : static::class;
        return new Proxy($target, $handlers);
    }

    /**
     * member value query handler
     * @param object|string $target - observable object
     * @param string $prop - object member name
     * @param null $value_or_args - irrelevant
     * @param Proxy $proxy - the proxy object from which the method is called
     * @return mixed - it is necessary to return the result
     */
    public static function get($target, string $prop, $value_or_args = null, Proxy $proxy)
    {
        if(is_string($target)){
            return $target::$$prop;
        }
        return $target->$prop;
    }

    /**
     * member value entry handler
     * @param object|string $target - observable object
     * @param string $prop - object member name
     * @param mixed $value_or_args - value to assign
     * @param Proxy $proxy - the proxy object from which the method is called
     * @return void
     */
    public static function set($target, string $prop, $value_or_args, Proxy $proxy): void
    {
        if(is_string($target)){
            $target::$$prop= $value_or_args;
        } else {
            $target->$prop = $value_or_args;
        }
    }

    /**
     * member delete handler
     * @param object|string $target - observable object
     * @param string $prop -  object member name
     * @param null $value_or_args -irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return void
     */
    public static function unset($target, string $prop, $value_or_args = null, Proxy $proxy): void
    {
        if(!is_string($target)){
            unset($target->$prop);
        } else {
            // although static deletion is prohibited, an error should be generated
            unset($target::$$prop);
        }
    }

    /**
     * checking is  set member handler
     * @param object|string $target - observable object
     * @param string $prop - object member name
     * @param null $value_or_args - irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return bool
     */
    public static function isset($target, string $prop, $value_or_args = null, Proxy $proxy): bool
    {
        if(is_string($target)){
            return isset($target::$$prop);
        } 
        return isset($target->$prop);
    }

    /**
     * Member call handler 
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param string $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function call($target, string $prop, array $value_or_args = [], Proxy $proxy)
    {
        if(is_string($target)){
            return $target::{$prop}(...$value_or_args);
        }
        $target->$prop(...$value_or_args);
    }    
    /**
     * Converting an object or class to a string
     * @param object|string $target - observable object
     * @param null $prop - irrelevant
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function invoke($target, $prop=null, array $value_or_args = [], Proxy $proxy)
    {
        if(is_string($target)){
            return ($target)(...$value_or_args);
        }
        return $target(...$value_or_args);
    } 
    /**
     * Converting an object or class to a string
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop - irrelevant
     * @param null $value_or_args  - irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return string
     */
    public static function toString($target, $prop=null, $value_or_args = null, Proxy $proxy):string
    {
        /*if(is_string($target)){
            return $target;
        }*/
        return ''.$target;
    }

    /**
     * creates an iterator for foreach
     * Returns an empty iterator for the class.
     * @param object|string $target - observable object
     * @param null $prop - irrelevant
     * @param null $value_or_args -irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return \Traversable
     */

    public static function iterator($target, $prop = null, $value_or_args = null, Proxy $proxy): \Traversable
    {       
        if(is_string($target)){
            return new ClassMembersIterator ($target);
        } else if ($target instanceof \IteratorAggregate) {
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }
}