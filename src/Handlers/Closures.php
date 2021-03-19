<?php


namespace Alpa\ProxyObject\Handlers;


use Alpa\ProxyObject\Proxy;

class Closures implements IContract
{
    use TClosures;
    public static  function static_run(string $action,object $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
    {
        
    }

}