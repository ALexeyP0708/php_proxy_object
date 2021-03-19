<?php


namespace Alpa\ProxyObject;


abstract class HandlersClass implements HandlersContract
{
    use HandlersStaticMethods;

    public function run(string $action,object $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
    {
        
    }
}