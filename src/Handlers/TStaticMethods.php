<?php


namespace Alpa\Tools\ProxyObject\Handlers;


use Alpa\Tools\ProxyObject\Proxy;
use Alpa\Tools\ProxyObject\ProxyAbstract;
use Alpa\Tools\ProxyObject\ProxyInterface;

trait TStaticMethods
{
    /**
     * @param string $action
     * @param object|string $target observable object/class
     * @param string|null $prop member of object/class
     * @param mixed|array|null $value_or_args if set action then value, else if call/invoke then arguments array else null
     * @param ProxyInterface $proxy
     * @return mixed|bool if isset action then boolean type else mixed
     * @throws \Exception
     */
    public static function &static_run(string $action, $target, ?string $prop, $value_or_args, ProxyInterface $proxy)
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
     * @deprecated
     * generates a proxy object
     * @param object|string $target
     * @param ActionsInterface|string|null $handlers
     * @param string|null $proxyClass
     * @return ProxyInterface
     * @throws \Exception
     */
    public static function proxy($target, $handlers = null, ?string $proxyClass = null): ProxyInterface
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return mixed it is necessary to return the result
     */
    public static function &get($target, string $prop, $value_or_args, ProxyInterface $proxy)
    {
        // This check is required, since when the result is returned by reference,
        // the property is dynamically created if it does not exist.      In PHP 8.2 this behavior is deprecated
        // Also in PHP, assigning a property by reference initializes it (this also applies to deleted properties). https://www.php.net/manual/en/language.references.whatdo.php
        
       // start check error
        $error_msg='';
        $error_code=0;
        $handler=set_error_handler(function (...$args) use (&$error_msg,&$error_code) {
            if(substr($args[1],0,19)==='Undefined property:'){
                $bt=debug_backtrace()[3];
                $error_msg=$args[1].' in '.$bt['file'] .' on line '. $bt['line']."\n";
                $error_code=$args[0];
                return true;
            }
            return false;
        },E_NOTICE|E_WARNING);
        if(is_string($target)){
            $res=$target::$$prop;
        } else {
            $res=$target->$prop;
        }
        restore_error_handler();
        //For some reason, the restored handler does not run when trigger_error
        if($error_code>0) {
            if($handler!==null){set_error_handler($handler);} // forcefully restore an error handler
            trigger_error($error_msg,$error_code===E_NOTICE?E_USER_NOTICE:E_USER_WARNING);
            if($handler!==null){restore_error_handler();}
            return $res;
        } 
        if(is_string($target)){
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return void
     */
    public static function set($target, string $prop, $value_or_args, ProxyInterface $proxy): void
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return void
     */
    public static function unset($target, string $prop, $value_or_args, ProxyInterface $proxy): void
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return bool
     */
    public static function isset($target, string $prop, $value_or_args, ProxyInterface $proxy): bool
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function & call($target, string $prop, array $value_or_args, ProxyInterface $proxy)
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function & invoke($target, $prop, array $value_or_args, ProxyInterface $proxy)
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
     * @param ProxyInterface $proxy
     * @return string
     */
    public static function toString($target, $prop, $value_or_args, ProxyInterface $proxy): string
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
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return \Traversable
     * @throws \Exception
     */
    public static function iterator($target, $prop, $value_or_args, ProxyInterface $proxy): \Traversable
    {
        if (is_string($target)) {
            return new ClassMembersIterator ($target);
        } else if ($target instanceof \IteratorAggregate) {
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }
}