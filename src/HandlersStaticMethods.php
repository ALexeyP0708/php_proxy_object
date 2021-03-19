<?php


namespace Alpa\ProxyObject;


trait HandlersStaticMethods
{
    public static function static_run (string $action, object $target,  ?string $prop=null,$value_or_args=null,Proxy $proxy)
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

    public static function getProxy($target): Proxy
    {
        return new Proxy($target,static::class);
    }
    /**
     * member value query handler
     * @param object $target - observable object
     * @param string $prop - object member name
     * @param null $value_or_args - irrelevant
     * @param Proxy $proxy - the proxy object from which the method is called
     * @return mixed - it is necessary to return the result
     */
    public static function get (object $target,string $prop,$value_or_args=null,Proxy $proxy)
    {
        return $target->$prop;
    }

    /**
     * member value entry handler
     * @param object $target - observable object
     * @param string $prop - object member name
     * @param mixed $value_or_args - value to assign
     * @param Proxy $proxy - the proxy object from which the method is called
     * @return void
     */
    public static function set (object $target,string $prop,$value_or_args,Proxy $proxy):void
    {
        $target->$prop=$value_or_args;
    }

    /**
     * member delete handler
     * @param object $target - observable object
     * @param string $prop -  object member name
     * @param null $value_or_args -irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return void
     */
    public static function unset (object $target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        unset($target->$prop);
    }

    /**
     * checking is  set member handler
     * @param object $target - observable object
     * @param string $prop - object member name
     * @param null $value_or_args - irrelevant
     * @param Proxy $proxy  the proxy object from which the method is called
     * @return bool
     */
    public static function isset (object $target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return isset($target->$prop);
    }

    /**
     * Member call handler
     * @param object $target - observable object
     * @param string $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    public static function call (object $target,string $prop,array $value_or_args=[],Proxy $proxy)
    {
        return $target->$prop(...$value_or_args);
    }

    /**
     * creates an iterator for foreach
     * @param object $target - observable object
     * @param null $prop - irrelevant
     * @param null $value_or_args -irrelevant
     * @param Proxy $proxy the proxy object from which the method is called
     * @return \Traversable
     */
    public static function iterator  (object $target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        if($target instanceof \IteratorAggregate){
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }
}