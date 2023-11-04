<?php


namespace Alpa\Tools\ProxyObject;


use Alpa\Tools\ProxyObject\Handlers\ActionsInterface;

abstract class ProxyAbstract implements ProxyInterface
{

    /**
     * @var object|string
     */
    protected  $target;
    /**
     * @var ActionsInterface|string  if type string then Handlers class name
     */
    protected $handlers;
    /**
     * @param string $action get|set|isset|unset|call|invoke|iterator
     * @param string|null $prop The "iterator" action does not pass this argument
     * @param null $value_or_arguments the value from the "set" and "call" actions
     * @return mixed  returned result of "get" "isset" "call" actions.
     */
    protected function &run(string $action, ?string $prop = null, $value_or_arguments = null)
    {
        self::refNoticeErrorHandler();
        if (is_string($this->handlers)) {
            $answer= & $this->handlers::static_run($action, $this->target, $prop, $value_or_arguments, $this);
        } else {
            $answer = & $this->handlers->run($action, $this->target, $prop, $value_or_arguments, $this);
        }
        restore_error_handler();
        return $answer;
    }
    // __get must return by reference
    public function &__get(string $name)
    {
        return $this->run('get', $name);
    }

    //Note:None of the arguments of these magic methods can be passed by reference.
    // https://www.php.net/manual/en/language.oop5.overloading.php
    public function __set(string $name, $value):void
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

    //Note:None of the arguments of these magic methods can be passed by reference.
    // https://www.php.net/manual/en/language.oop5.overloading.php
    public function &__call($name, $arguments)
    {
        return $this->run('call', $name, $arguments);
    }

    //Note:None of the arguments of these magic methods can be passed by reference.
    // https://www.php.net/manual/en/language.oop5.overloading.php
    public function &__invoke(...$arguments)
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
    private  static function refNoticeErrorHandler()
    {
        $prev_handler_error = null;
        $prev_handler_error = set_error_handler(function (...$args) use (&$prev_handler_error) {

            if (in_array($args[1],[
                'Only variables should be assigned by reference',
                'Only variable references should be returned by reference'
            ])) {
                return true;
            }
            if(!is_null($prev_handler_error)){
                $answer=$prev_handler_error(...$args);
                if(is_bool($answer)){
                    return $answer;
                }
            }
            return false;
        }, E_NOTICE|E_WARNING);
    }
}