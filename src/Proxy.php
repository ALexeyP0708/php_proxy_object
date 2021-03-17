<?php
namespace Alpa\ProxyObject;

class Proxy implements \IteratorAggregate
{
    protected object $target;
    protected Handlers $handlers;

    public function __construct(object $target,Handlers $handlers)
    {
        $this->target=$target;
        $this->handlers=$handlers;
    }
    public function __get(string $name)
    {
        return $this->handlers->runGet($this->target,$name,$this);
    }
    public function __set(string $name,$value)
    {
        $this->handlers->runSet($this->target,$name,$value,$this);
    }
    public function __isset(string $name) :bool
    {
        return $this->handlers->runIsset($this->target,$name,$this);
    }
    public function __unset(string $name):void
    {
        $this->handlers->runUnset($this->target,$name,$this);
    }
    public function __call($name,$arguments )
    {
        return $this->handlers->runCall($this->target,$name,$arguments,$this);
    }
    public function getIterator(): \Traversable
    {
        return $this->handlers->runIterator($this->target,$this);
    }
}