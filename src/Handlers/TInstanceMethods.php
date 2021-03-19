<?php

namespace Alpa\ProxyObject\Handlers;

use Alpa\ProxyObject\Proxy;

trait TInstanceMethods
{
    use TStaticMethods;

    public function run(string $action, object $target, ?string $prop = null, $value_or_args = null, Proxy $proxy)
    {
        if (!in_array($action, ['get', 'set', 'isset', 'unset', 'call', 'iterator'])) {
            throw new \Exception('Action must be one of the values "get|set|isset|unset|call|iterator"');
        }
        $method = $action;
        $methodProp = null;
        if ($method !== 'iterator') {
            $methodProp = $method . '_' . $prop;
        }
        if ($methodProp !== null && method_exists(static::class, $methodProp)) {
            $method = $methodProp;
        }
        return $this->$method($target, $prop, $value_or_args, $proxy);
    }

    public function newProxy($target)
    {
        return static::proxy($target, $this);
    }

    protected function get(object $target, string $prop, $value_or_args = null, Proxy $proxy)
    {
        return static::static_get($target, $prop, $value_or_args, $proxy);
    }

    protected function set(object $target, string $prop, $value_or_args = null, Proxy $proxy): void
    {
        static::static_set($target, $prop, $value_or_args, $proxy);
    }

    protected function isset(object $target, string $prop, $value_or_args = null, Proxy $proxy): bool
    {
        return static::static_isset($target, $prop, $value_or_args, $proxy);
    }

    protected function unset(object $target, string $prop, $value_or_args = null, Proxy $proxy): void
    {
        static::static_unset($target, $prop, $value_or_args, $proxy);
    }

    protected function call(object $target, string $prop, array $value_or_args = [], Proxy $proxy)
    {
        return static::static_call($target, $prop, $value_or_args, $proxy);
    }

    protected function iterator(object $target, $prop, $value_or_args = null, Proxy $proxy)
    {
        return static::static_iterator($target, $prop, $value_or_args, $proxy);
    }
}