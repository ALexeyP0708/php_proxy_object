<?php


namespace Alpa\Tools\ProxyObject\Handlers;


class  ClassMembersIterator implements \Iterator
{
    protected int $key = 0;
    protected array $props = [];

    /**
     * IteratorOfClassMembers constructor.
     * @param string $target
     */
    public function __construct(string $target)
    {
        $this->target = $target;
        $this->rewind();
    }

    public function rewind()
    {
        $this->key = 0;
        $this->props = array_keys(get_class_vars($this->target));
    }

    public function key()
    {
        return $this->props[$this->key] ?? null;
    }

    public function current()
    {
        $prop = $this->key();
        return $this->target::$$prop;
    }

    public function next()
    {
        $this->key++;
    }

    public function valid()
    {
        $prop = $this->key();
        return $prop !== null;
    }
}