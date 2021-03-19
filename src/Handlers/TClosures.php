<?php


namespace Alpa\ProxyObject\Handlers;


use Alpa\ProxyObject\Proxy;

trait TClosures
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
     * @param Proxy $proxy
     * @return mixed
     */
    protected function runGet(object $target,string $prop,Proxy $proxy)
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
    protected function runSet(object $target,string $prop,$value,Proxy $proxy):void
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
    protected function runIsset(object $target,string $prop,Proxy $proxy):bool
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
    protected function runUnset(object $target,string $prop,Proxy $proxy) :void
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
    protected function runCall(object $target,string $prop,array $arguments,Proxy $proxy)
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
    protected function runIterator($target,Proxy $proxy):\Traversable
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
}