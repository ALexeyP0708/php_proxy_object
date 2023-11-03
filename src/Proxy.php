<?php

namespace Alpa\Tools\ProxyObject;

use Alpa\Tools\ProxyObject\Handlers\ActionsInterface;

final class Proxy extends ProxyAbstract
{

    /**
     * Proxy constructor.
     * @param object|string $target if type string then then this class name
     * @param ActionsInterface|string $handlers if type string then Handlers class name
     */
    public function __construct($target, $handlers)
    {
        if(is_object($target) || is_string($target) && class_exists($target)){
            $this->target = $target;    
        } else {
            throw new \Exception('argument 1: must be an object or class name');
        }
        
        if (
            is_object($handlers) && $handlers instanceof ActionsInterface ||
            is_string($handlers) && is_subclass_of($handlers, ActionsInterface::class)
        ) {
            $this->handlers = $handlers;
        } else {
            throw new \Exception('argument 2: the object must implement interface' . ActionsInterface::class .
                ', or if class name, then the class must implement interface ' . ActionsInterface::class);
        }
    }
    
}