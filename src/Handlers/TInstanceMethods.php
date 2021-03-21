<?php

namespace Alpa\ProxyObject\Handlers;

use Alpa\ProxyObject\Proxy;

trait TInstanceMethods
{
    public function run(string $action, $target, ?string $prop = null, $value_or_args = null, Proxy $proxy)
    {
        if (!in_array($action, ['get', 'set', 'isset', 'unset', 'call','invoke' ,'iterator'])) {
            throw new \Exception('Action must be one of the values "get|set|isset|unset|call|iterator"');
        }
        $method = $action;
        $methodProp = null;
        if (!in_array($action,['iterator','invoke'])) {
            $methodProp = $method . '_' . $prop;
        }
        if ($methodProp !== null && method_exists(static::class, $methodProp)) {
            $method = $methodProp;
        }
        return $this->$method($target, $prop, $value_or_args, $proxy);
    }

    public function newProxy($target)
    {
        return TStaticMethods::proxy($target, $this);
    }

    public function get($target, string $prop, $value_or_args = null, Proxy $proxy)
    {
        return TStaticMethods::get($target, $prop, $value_or_args, $proxy);
    }

    public function set($target, string $prop, $value_or_args = null, Proxy $proxy): void
    {
        TStaticMethods::set($target, $prop, $value_or_args, $proxy);
    }

    public function isset($target, string $prop, $value_or_args = null, Proxy $proxy): bool
    {
        return TStaticMethods::isset($target, $prop, $value_or_args, $proxy);
    }

    public function unset($target, string $prop, $value_or_args = null, Proxy $proxy): void
    {
        TStaticMethods::unset($target, $prop, $value_or_args, $proxy);
    }

    public function call($target, string $prop, array $value_or_args = [], Proxy $proxy)
    {
        return TStaticMethods::call($target, $prop, $value_or_args, $proxy);
    }
    
    public function invoke($target, $prop=null, array $value_or_args = [], Proxy $proxy)
    {
        return TStaticMethods::invoke($target, $prop, $value_or_args, $proxy);
    }

    public function iterator($target, $prop, $value_or_args = null, Proxy $proxy)
    {
        return TStaticMethods::iterator($target, $prop, $value_or_args, $proxy);
    }
}