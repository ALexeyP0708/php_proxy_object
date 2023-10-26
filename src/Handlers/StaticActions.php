<?php


namespace Alpa\Tools\ProxyObject\Handlers;


use Alpa\Tools\ProxyObject\Proxy;

abstract class StaticActions implements ActionsInterface
{
    use TStaticMethods {
        get as protected;
        set as protected;
        unset as protected;
        isset as protected;
        call as protected;
        invoke as protected;
        toString as protected;
        iterator as protected;
    }

    protected static function getActionPrefix(): string
    {
        return '';
    }

    public function &run(string $action, $target, ?string $prop, $value_or_args, Proxy $proxy)
    {

    }
}