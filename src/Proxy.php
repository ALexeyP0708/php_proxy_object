<?php
namespace Alpa\ProxyObject;

class Proxy implements \IteratorAggregate
{
    protected object $target;
    /**
     * @var Handlers|HandlersClass 
     */
    protected  $handlers;

    /**
     * Proxy constructor.
     * @param object|callable $target
     * @param Handlers|HandlersClass $handlers
     */
    public function __construct($target, $handlers)
    {
        $this->target=$target;
        $this->handlers=$handlers;
    }
    public function __get(string $name)
    {
        if(is_subclass_of($this->handlers,HandlersClass::class)){
            return $this->handlers::run('get',$this->target,$name,null,$this);
        }
        return $this->handlers->runGet($this->target,$name,$this);
    }
    public function __set(string $name,$value)
    {
        if(is_subclass_of($this->handlers,HandlersClass::class)){
            $this->handlers::run('set',$this->target,$name,$value,$this);
        } else{
            $this->handlers->runSet($this->target,$name,$value,$this);
        }
    }
    public function __isset(string $name) :bool
    {
        if(is_subclass_of($this->handlers,HandlersClass::class)){
            return $this->handlers::run('isset',$this->target,$name,null,$this);
        }
        return $this->handlers->runIsset($this->target,$name,$this);
    }
    public function __unset(string $name):void
    {
        if(is_subclass_of($this->handlers,HandlersClass::class)){
            $this->handlers::run('unset',$this->target,$name,null,$this);
        } else{
            $this->handlers->runUnset($this->target,$name,$this);
        }
    }
    public function __call($name,$arguments )
    {
        if(is_subclass_of($this->handlers,HandlersClass::class)){
            return $this->handlers::run('call',$this->target,$name,$arguments,$this);
        }
        return $this->handlers->runCall($this->target,$name,$arguments,$this);
    }
    public function getIterator(): \Traversable
    {
        if(is_subclass_of($this->handlers,HandlersClass::class)){
            return $this->handlers::run('iterator',$this->target,null,null,$this);
        }
        return $this->handlers->runIterator($this->target,$this);
    }
}