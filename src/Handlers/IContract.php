<?php


namespace Alpa\ProxyObject\Handlers;

use Alpa\ProxyObject\Proxy;

interface IContract
{
    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param null $value_or_arguments
     * @param Proxy $proxy
     * @return mixed
     */
    public function run(string $action, $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy);
    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param null $value_or_arguments
     * @param Proxy $proxy
     * @return mixed
     */
    public static function static_run(string $action, $target, ?string $prop = null, $value_or_arguments = null, Proxy $proxy);
}