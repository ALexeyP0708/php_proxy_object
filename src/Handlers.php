<?php


namespace Alpa\ProxyObject;


use SebastianBergmann\FileIterator\Iterator;

class Handlers implements HandlersContract
{
    protected array $properties=[
        'get'=>[],
        'set'=>[],
        'isset'=>[],
        'unset'=>[],
        'call'=>[]
    ];
    protected ?\Closure $get=null;
    protected ?\Closure $set=null;
    protected ?\Closure $unset=null;
    protected ?\Closure $isset=null;
    protected ?\Closure $call=null;
    protected ?\Closure $iterator=null;

    public function __construct(array $handlers=[],array $handlersProp=[])
    {
        foreach($handlers as $member=>$call){
            $this->init($member,$call);
        }
        foreach($handlers as $member=>$props){
            foreach($props as $prop=>$call){
                $this->initProp($member,$prop,$call);
            }
        }
    }
    public function run(string $action,object $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
    {
        switch ($action){
            case 'get':
                return $this->runGet($target,$prop,$proxy);
            case 'set':
                $this->runSet($target,$prop,$value_or_arguments,$proxy);
                break;
            case 'isset':
                return $this->runIsset($target,$prop,$proxy);
            case 'unset':
                $this->runUnset($target,$prop,$proxy);
                break;
            case 'call':
                return  $this->runCall($target,$prop,$value_or_arguments,$proxy);
            case 'iterator':
                return  $this->runIterator($target,$proxy);
        }
    }

    /**
     * initializes handlers for specific actions (get | set | unset | isset | call)
     * @param string $action get | set | unset | isset | call The action for which you want to install the handler
     * @param callable $handler  A handler that will process a specific action
     * @return bool  Indicates whether a handler is set
     */
    public function init(string $action,callable $handler):bool
    {
        if(!property_exists($this,$action) || $this->$action!==null){
            return false;
        }
        if(!($handler instanceof \Closure)){
            $handler=\Closure::fromCallable($handler);
        }
        $this->$action=$handler;
        return true;
    }

    /**
     * initializes handlers for specific actions properties
     * @param string $action get | set | unset | isset | call The action for which you want to install the handler
     * @param string $prop the property for which the handler is intended
     * @param callable $handler  A handler that will process a specific property action
     * @return bool  Indicates whether a handler is set
     */
    public function initProp(string $action,string $prop,callable $handler):bool
    {

        if(!array_key_exists($action,$this->properties) || array_key_exists($prop,$this->properties[$action])){
            return false;
        }
        if(!($handler instanceof \Closure)){
            $handler=\Closure::fromCallable($handler);
        }
        $this->properties[$action][$prop]=$handler;
        return true;
    }

    /**
     * runs handler for the property with the 'get' action
     * @param object $target
     * @param string $prop
     * @param Proxy|null $proxy
     * @return mixed
     */
    public function runGet(object $target,string $prop,?Proxy $proxy=null)
    {
        $action='get';
        if(array_key_exists($prop,$this->properties[$action])){
            return $this->properties[$action][$prop]($target,$prop,$proxy);
        } else
            if($this->$action!==null){
                $call=$this->$action;
                return $call($target,$prop,$proxy);
            }
        return $target->$prop;
    }

    /**
     * runs handler for the property with the 'set' action
     * @param object $target
     * @param string $prop
     * @param $value
     * @param Proxy $proxy
     * @return mixed
     */
    public function runSet(object $target,string $prop,$value,Proxy $proxy):void
    {
        $action='set';
        if(array_key_exists($prop,$this->properties[$action])){
            $this->properties[$action][$prop]($target,$prop,$value,$proxy);
        } else
            if($this->$action!==null){
                $call=$this->$action;
                $call($target,$prop,$value,$proxy);
            } else {
                $target->$prop=$value;
            }
    }

    /**
     * runs handler for the property with the 'isset' action
     * @param object $target
     * @param string $prop
     * @param Proxy $proxy
     * @return bool
     */
    public function runIsset(object $target,string $prop,Proxy $proxy):bool
    {
        $action='isset';
        if(array_key_exists($prop,$this->properties[$action])){
            return $this->properties[$action][$prop]($target,$prop,$proxy);
        } else
            if($this->$action!==null){
                $call=$this->$action;
                return $call($target,$prop,$proxy);
            }
        return isset($target->$prop);
    }

    /**
     * runs handler for the property with the 'unset' action
     * @param object $target
     * @param string $prop
     * @param Proxy|null $proxy
     * @return void
     */
    public function runUnset(object $target,string $prop,Proxy $proxy) :void
    {
        $action='unset';
        if(array_key_exists($prop,$this->properties[$action])){
            $this->properties[$action][$prop]($target,$prop,$proxy);
        } else
            if($this->$action!==null){
                $call=$this->$action;
                $call($target,$prop,$proxy);
            } else {
                unset($target->$prop);
            }

    }

    /**
     * runs handler for the property with the 'unset' action
     * @param object $target
     * @param string $prop
     * @param array $arguments
     * @param Proxy $proxy
     * @return mixed
     */
    public function runCall(object $target,string $prop,array $arguments,Proxy $proxy)
    {
        $action='call';
        if(array_key_exists($prop,$this->properties[$action])){
            return $this->properties[$action][$prop]($target,$prop,$arguments,$proxy);
        } else
            if($this->$action!==null){
                $call=$this->$action;
                return $call($target,$prop,$arguments,$proxy);
            }
        return $target->$prop(...$arguments);
    }

    /**
     * @param $target
     * @param Proxy|null $proxy
     * @return \Traversable
     * @throws \Exception
     */
    public function runIterator($target,Proxy $proxy):\Traversable
    {
        if($this->iterator!==null){
            $call=$this->iterator;
            return $call($target,$proxy);
        }
        if($target instanceof \IteratorAggregate){
            return $target->getIterator();
        }
        return new \ArrayIterator($target);
    }


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