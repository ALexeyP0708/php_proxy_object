<?php

namespace Alpa\Tools\ProxyObject\Handlers;

use Alpa\Tools\ProxyObject\ProxyInterface;

trait TInstanceMethods
{
    /**
     * @param string $action
     * @param object|string $target observable object/class
     * @param string|null $prop
     * @param mixed|null $value_or_args
     * @param ProxyInterface $proxy
     * @return mixed
     * @throws \Exception
     */
    public function & run(string $action, $target, ?string $prop, $value_or_args, ProxyInterface $proxy)
    {
        if (!in_array($action, ['get', 'set', 'isset', 'unset', 'call', 'invoke', 'toString', 'iterator'])) {
            throw new \Exception('Action must be one of the values "get|set|isset|unset|call|invoke|toString|iterator"');
        }
        $method = $action;
        $methodProp = null;
        if (!in_array($action, ['iterator', 'invoke'])) {
            $methodProp = $method . '_' . $prop;
        }
        if ($methodProp !== null && method_exists(static::class, $methodProp)) {
            $method = $methodProp;
        }
        return $this->$method($target, $prop, $value_or_args, $proxy);
    }

    /**
     * @deprecated 
     * @param object|string $target observable object/class
     * @param string|null $proxyClass
     * @return ProxyInterface
     */
    public function newProxy($target, ?string $proxyClass = null): ProxyInterface
    {
        return TStaticMethods::proxy($target, $this, $proxyClass);
    }

    /**
     * Get action.
     * Member value query handler
     * @param object|string $target observable object/class
     * @param string $prop
     * @param null $value_or_args
     * @param ProxyInterface $proxy
     * @return mixed
     */
    public function & get($target, string $prop, $value_or_args, ProxyInterface $proxy)
    {
        return TStaticMethods::get($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Set action.
     * member value entry handler
     * @param object|string $target observable object/class
     * @param string $prop
     * @param mixed $value_or_args
     * @param ProxyInterface $proxy
     */
    public function set($target, string $prop, $value_or_args, ProxyInterface $proxy): void
    {
        TStaticMethods::set($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Isset action.
     * Checking is  set member handler
     * @param object|string $target observable object/class
     * @param string $prop
     * @param null $value_or_args
     * @param ProxyInterface $proxy
     * @return bool
     */
    public function isset($target, string $prop, $value_or_args, ProxyInterface $proxy): bool
    {
        return TStaticMethods::isset($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Unset action.
     * Member delete handler
     * @param object|string $target observable object/class
     * @param string $prop
     * @param null $value_or_args
     * @param ProxyInterface $proxy
     */
    public function unset($target, string $prop, $value_or_args, ProxyInterface $proxy): void
    {
        TStaticMethods::unset($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Call action.
     * Member call handler
     * by default the member in target must be a method
     * @param object|string $target - observable object/class
     * @param string $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return mixed
     */
    public function & call($target, string $prop, array $value_or_args, ProxyInterface $proxy)
    {
        return TStaticMethods::call($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Invoke action.
     * Object or class invoke.
     * @param object|string $target observable object/class
     * @param null $prop
     * @param array $value_or_args
     * @param ProxyInterface $proxy
     * @return mixed
     */
    public function & invoke($target, $prop, array $value_or_args, ProxyInterface $proxy)
    {
        return TStaticMethods::invoke($target, $prop, $value_or_args, $proxy);
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
    public function toString($target, $prop, $value_or_args, ProxyInterface $proxy): string
    {
        return TStaticMethods::toString($target, $prop, $value_or_args, $proxy);
    }

    /**
     * Iterator action.
     * creates an iterator for foreach
     * Returns an empty iterator for the class.
     * @param object|string $target observable object
     * @param null $prop irrelevant
     * @param null $value_or_args irrelevant
     * @param ProxyInterface $proxy the proxy object from which the method is called
     * @return \Traversable
     * @throws \Exception
     */
    public function iterator($target, $prop, $value_or_args, ProxyInterface $proxy): \Traversable
    {
        return TStaticMethods::iterator($target, $prop, $value_or_args, $proxy);
    }
}