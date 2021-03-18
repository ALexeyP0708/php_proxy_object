<?php


namespace Alpa\ProxyObject;


use SebastianBergmann\FileIterator\Iterator;

abstract class HandlersClass
{
    public static function getProxy($target): Proxy
    {
        return new Proxy($target,static::class);
    }
    public static function run (string $action, object $target,  ?string $prop=null,$value_or_args=null,?Proxy $proxy=null)
    {
        $method=null;
        if(!in_array($action,['get','set','isset','unset','call','iterator'])){
           throw new \Exception('Action must be one of the values "get|set|isset|unset|call|iterator"'); 
        }
        $method=$action;
        $methodProp=null;
        if($method!=='iterator'){
            $methodProp=$method.'_'.$prop;
        }
        if($methodProp!==null && method_exists(static::class,$methodProp)){
            $method=$methodProp;
        }
        return call_user_func([static::class,$method],$target,$prop,$value_or_args,$proxy);
    }
    
    public static function get (object $target,string $prop,$value_or_args=null,Proxy $proxy)
    {
        return $target->$prop;
    }

    public static function set (object $target,string $prop,$value_or_args,Proxy $proxy):void
    {
        $target->$prop=$value_or_args;
    }

    public static function unset (object $target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        unset($target->$prop);
    }

    public static function isset (object $target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return isset($target->$prop);
    }

    public static function call (object $target,string $prop,array $value_or_args=[],Proxy $proxy)
    {
        return $target->$prop(...$value_or_args);
    }

    public static function iterator  (object $target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        if($target instanceof \IteratorAggregate){
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }
}