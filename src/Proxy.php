<?php

namespace Alpa\Tools\ProxyObject;

use Alpa\Tools\ProxyObject\Handlers\IContract;

class Proxy implements \IteratorAggregate
{
    /**
     * @var object|string 
     */
    protected  $target;
    /**
     * @var IContract|string  if type string then Handlers class name
     */
    protected $handlers;

    /**
     * Proxy constructor.
     * @param object|string $target if type string then then this class name
     * @param IContract|string $handlers if type string then Handlers class name
     */
    public function __construct($target, $handlers)
    {
        if(is_object($target) || is_string($target) && class_exists($target)){
            $this->target = $target;    
        } else {
            throw new \Exception('argument 1: must be an object or class name');
        }
        
        if (
            is_object($handlers) && $handlers instanceof IContract ||
            is_string($handlers) && is_subclass_of($handlers, IContract::class)
        ) {
            $this->handlers = $handlers;
        } else {
            throw new \Exception('argument 2: the object must implement interface' . IContract::class .
                ', or if class name, then the class must implement interface ' . IContract::class);
        }
    }

    /**
     * @param string $action get|set|isset|unset|call|invoke|iterator
     * @param string|null $prop The "iterator" action does not pass this argument
     * @param null $value_or_arguments the value from the "set" and "call" actions
     * @return mixed  returned result of "get" "isset" "call" actions. 
     */
    protected function run(string $action, ?string $prop = null, $value_or_arguments = null)
    {
        if (is_string($this->handlers)) {
            return $this->handlers::static_run($action, $this->target, $prop, $value_or_arguments, $this);
        } else {
            return $this->handlers->run($action, $this->target, $prop, $value_or_arguments, $this);
        }
    }

    public function __get(string $name)
    {
        return $this->run('get', $name);
    }

    public function __set(string $name, $value)
    {
        $this->run('set', $name, $value);
    }

    public function __isset(string $name): bool
    {
        return (bool)$this->run('isset', $name);
    }

    public function __unset(string $name): void
    {
        $this->run('unset', $name);
    }

    public function __call($name, $arguments)
    {
        return $this->run('call', $name, $arguments);
    }
    public function __invoke(...$arguments)
    {
        return $this->run('invoke', null, $arguments);
    }    
    public function __toString():string
    {
        return $this->run('toString', null, null);
    }
    public function getIterator(): \Traversable
    {
        $iterator = $this->run('iterator');
        // If something returns non-null and does not implement \Traversable then it should throw an Exception.
        if ($iterator !== null) {
            return $iterator;
        }
        return new \ArrayIterator([]);
    }
}