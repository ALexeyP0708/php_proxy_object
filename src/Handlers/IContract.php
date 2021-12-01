<?php


namespace Alpa\ProxyObject\Handlers;

use Alpa\ProxyObject\Proxy;

interface IContract
{
    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param mixed|array|null $value_or_arguments
     * @param Proxy $proxy
     * @return mixed
     */
    public function run(string $action, $target,?string $prop,$value_or_arguments,Proxy $proxy);
    
    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param mixed|array|null $value_or_args
     * @param Proxy $proxy
     * @return mixed
     */
    public static function static_run(string $action, $target, ?string $prop, $value_or_args, Proxy $proxy);
}