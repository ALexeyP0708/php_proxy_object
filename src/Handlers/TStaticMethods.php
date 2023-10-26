<?php


namespace Alpa\Tools\ProxyObject\Handlers;


use Alpa\Tools\ProxyObject\Proxy;

trait TStaticMethods
{
    /**
     * @param string $action
     * @param object|string $target observable object/class
     * @param string|null $prop member of object/class
     * @param mixed|array|null $value_or_args if set action then value, else if call/invoke then arguments array else null
     * @param Proxy $proxy
     * @return mixed|bool if isset action then boolean type else mixed
     * @throws \Exception
     */
    public static function &static_run(string $action, $target, ?string $prop, $value_or_args, Proxy $proxy)
    {
        if (!in_array($action, ['get', 'set', 'isset', 'unset', 'call', 'invoke', 'toString', 'iterator'])) {
            throw new \Exception('Action must be one of the values "get|set|isset|unset|call|invoke|toString|iterator"');
        }
        $method = static::getActionPrefix() . $action;
        $methodProp = null;
        if (!in_array($action, ['iterator', 'invoke'])) {
            $methodProp = $method . '_' . $prop;
        }
        if ($methodProp !== null && method_exists(static::class, $methodProp)) {
            $method = $methodProp;
        }
        return static::{$method}($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Returns the prefix of actions (methods)
     * It must be overridden if in the class the methods of traits will be used under an alias with the appropriate prefix.
     * @return string
     */
    protected static function getActionPrefix(): string
    {
        return '';
    }

    /**
     * generates a proxy object
     * @param object|string $target
     * @param ActionsInterface|string|null $handlers
     * @param string|null $proxyClass
     * @return Proxy
     * @throws \Exception
     */
    public static function proxy($target, $handlers = null, ?string $proxyClass = null): Proxy
    {
        $handlers = $handlers !== null ? $handlers : static::class;
        $proxyClass = $proxyClass ?? $handlers::$proxyClass ?? Proxy::class;
        return new $proxyClass($target, $handlers);
    }

    /**
     * Get action.
     * Member value query handler
     * @param object|string $target observable object/class
     * @param string $prop object member name
     * @param null $value_or_args irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed it is necessary to return the result
     */
    public static function &get($target, string $prop, $value_or_args, Proxy $proxy)
    {
        // This check is required, since when the result is returned by reference, the property is dynamically created if it does not exist.
        // In PHP 8.2 this behavior is deprecated
        // start check error
        $answer=null;
        $before_error_handler=null;
        $check=false;
        $before_error_handler=set_error_handler(function(...$args)use(&$before_error_handler,&$check){
            
            if(substr($args[1],0,18)==='Undefined property') {
                $check = true;
            }
            return !is_null($before_error_handler) ? $before_error_handler(...$args):false;
        },E_NOTICE|E_WARNING);
        if(is_string($target)){
            $target::$$prop;
        } else {
            $target->$prop;
        }
        restore_error_handler();
        if($check){
            return $answer;
        }
        // end check error
        if (is_string($target)) {
            return $target::$$prop;
        }
        return $target->$prop;
    }

    /**
     * Set action.
     * member value entry handler
     * @param object|string $target observable object/class
     * @param string $prop object member name
     * @param mixed $value_or_args value to assign
     * @param Proxy $proxy the proxy object from which the method is called
     * @return void
     */
    public static function set($target, string $prop, $value_or_args, Proxy $proxy): void
    {
        if (is_string($target)) {
            $target::$$prop = $value_or_args;
        } else {
            $target->$prop = $value_or_args;
        }
    }

    /**
     * Unset action.
     * Member delete handler
     * @param object|string $target observable object/class
     * @param string $prop object member name
     * @param null $value_or_args irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return void
     */
    public static function unset($target, string $prop, $value_or_args, Proxy $proxy): void
    {
        if (!is_string($target)) {
            unset($target->$prop);
        } else {
            // although static deletion is prohibited, an error should be generated
            unset($target::$$prop);
        }
    }

    /**
     * Isset action.
     * checking is  set member handler
     * @param object|string $target observable object/class
     * @param string $prop object member name
     * @param null $value_or_args irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return bool
     */
    public static function isset($target, string $prop, $value_or_args, Proxy $proxy): bool
    {
        if (is_string($target)) {
            return isset($target::$$prop);
        }
        return isset($target->$prop);
    }

    /**
     * Call action
     * Member call handler
     * by default the member in target must be a method
     * @param object|string $target observable object/class
     * @param string $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function & call($target, string $prop, array $value_or_args, Proxy $proxy)
    {
        if (is_string($target)) {
            return $target::{$prop}(...$value_or_args);
        }
        return $target->$prop(...$value_or_args);
    }

    /**
     * Invoke action.
     * Object or class invoke.
     * @param object|string $target observable object/class
     * @param null $prop irrelevant
     * @param array $value_or_args arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function & invoke($target, $prop, array $value_or_args, Proxy $proxy)
    {
        if (is_string($target)) {
            return ($target)(...$value_or_args);
        }
        return $target(...$value_or_args);
    }

    /**
     * ToString action.
     * Converting an object or class to a string
     * by default the member in target must be a method
     * @param object|string $target observable object/class
     * @param null $prop
     * @param null $value_or_args
     * @param Proxy $proxy
     * @return string
     */
    public static function toString($target, $prop, $value_or_args, Proxy $proxy): string
    {
        /*if(is_string($target)){
            return $target;
        }*/
        return '' . $target;
    }

    /**
     * Iterator action.
     * creates an iterator for foreach
     * Returns an empty iterator for the class.
     * @param object|string $target observable object/class
     * @param null $prop irrelevant
     * @param null $value_or_args irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return \Traversable
     * @throws \Exception
     */
    public static function iterator($target, $prop, $value_or_args, Proxy $proxy): \Traversable
    {
        if (is_string($target)) {
            return new ClassMembersIterator ($target);
        } else if ($target instanceof \IteratorAggregate) {
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }
}