<?php


namespace Alpa\Tools\ProxyObject;


interface ProxyInterface extends \IteratorAggregate
{
    public function &__get(string $name);
    public function __set(string $name, $value):void;
    public function __isset(string $name): bool;
    public function __unset(string $name): void;
    public function &__call($name, $arguments);
    public function &__invoke(...$arguments);
    public function __toString():string;
    public function getIterator(): \Traversable;
}