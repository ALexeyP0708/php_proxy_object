<?php


namespace Alpa\Tools\ProxyObject\Handlers;

abstract class Instance implements ActionsInterface
{
    use TInstanceMethods, TStaticMethods {
        TInstanceMethods::get insteadof TStaticMethods;
        TInstanceMethods::get as protected;
        TInstanceMethods::set insteadof TStaticMethods;
        TInstanceMethods::set as protected;
        TInstanceMethods::unset insteadof TStaticMethods;
        TInstanceMethods::unset as protected;
        TInstanceMethods::isset insteadof TStaticMethods;
        TInstanceMethods::isset as protected;
        TInstanceMethods::call insteadof TStaticMethods;
        TInstanceMethods::call as protected;
        TInstanceMethods::invoke insteadof TStaticMethods;
        TInstanceMethods::invoke as protected;
        TInstanceMethods::toString insteadof TStaticMethods;
        TInstanceMethods::toString as protected;
        TInstanceMethods::iterator insteadof TStaticMethods;
        TInstanceMethods::iterator as protected;
        TStaticMethods::get as protected static_get;
        TStaticMethods::set as protected static_set;
        TStaticMethods::unset as protected static_unset;
        TStaticMethods::isset as protected static_isset;
        TStaticMethods::call as protected static_call;
        TStaticMethods::invoke as protected static_invoke;
        TStaticMethods::toString as protected static_toString;
        TStaticMethods::iterator as protected static_iterator;
    }

    protected static function getActionPrefix(): string
    {
        return 'static_';
    }
}