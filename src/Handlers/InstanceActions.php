<?php


namespace Alpa\ProxyObject\Handlers;


use Alpa\ProxyObject\Proxy;

abstract class InstanceActions implements IContract
{
    use TInstanceMethods {
        TInstanceMethods::get as protected;
        TInstanceMethods::set as protected;
        TInstanceMethods::unset as protected;
        TInstanceMethods::isset as protected;
        TInstanceMethods::call as protected;
        TInstanceMethods::iterator as protected;
    }
    public static function static_run(string $action, $target, ?string $prop = null, $value_or_args = null, Proxy $proxy)
    {

    }
}