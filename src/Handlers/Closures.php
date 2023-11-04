<?php


namespace Alpa\Tools\ProxyObject\Handlers;

use Alpa\Tools\ProxyObject\ProxyInterface;

class Closures implements ActionsInterface
{
    protected array $properties = [
        'get' => [],
        'set' => [],
        'isset' => [],
        'unset' => [],
        'call' => []
    ];
    protected ?\Closure $get = null;
    protected ?\Closure $set = null;
    protected ?\Closure $unset = null;
    protected ?\Closure $isset = null;
    protected ?\Closure $call = null;
    protected ?\Closure $invoke = null;
    protected ?\Closure $iterator = null;
    protected ?\Closure $toString = null;

    /**
     * Closures constructor.
     * @param array $handlers Handlers for actions. Actions - get|set|isset|unset|call|iterator|invoke|toString
     * @param array $handlersProp Handlers for members actions. Actions - get|set|isset|unset|call|iterator|invoke|toString
     */
    public function __construct(array $handlers = [], array $handlersProp = [])
    {
        foreach ($handlers as $member => $call) {
            $this->init($member, $call);
        }
        foreach ($handlers as $member => $props) {
            foreach ($props as $prop => $call) {
                $this->initProp($member, $prop, $call);
            }
        }
    }

    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param array|mixed $value_or_arguments
     * @param ProxyInterface $proxy
     * @return bool|mixed|string|\Traversable
     * @throws \Exception
     */
    public function & run(string $action, $target, ?string $prop, $value_or_arguments, ProxyInterface $proxy)
    {
        $answer=null;
        switch ($action) {
            case 'get':
                $answer= & $this->runGet($target, $prop, $proxy);
                break;
            case 'set':
                $this->runSet($target, $prop, $value_or_arguments, $proxy);
                break;
            case 'isset':
                $answer = $this->runIsset($target, $prop, $proxy);
                break;
            case 'unset':
                $this->runUnset($target, $prop, $proxy);
                break;
            case 'call':
                $answer = & $this->runCall($target, $prop, $value_or_arguments, $proxy);
                break;
            case 'invoke':
                $answer = &  $this->runInvoke($target, $value_or_arguments, $proxy);
                break;
            case 'toString':
                $answer = $this->runToString($target, $proxy);
                break;
            case 'iterator':
                $answer = $this->runIterator($target, $proxy);
                break;
        }
        return $answer;
    }

    /**
     * initializes handlers for specific actions
     *
     * @param string $action Actions - get | set | unset | isset | call | invoke | iterator | toString .
     * @param callable $handler A handler that will process a specific action
     * @return bool  Indicates whether a handler is set
     */
    public function init(string $action, callable $handler): bool
    {
        if (!property_exists($this, $action) || $this->$action !== null) {
            return false;
        }
        if (!($handler instanceof \Closure)) {
            $handler = \Closure::fromCallable($handler);
        }
        $this->$action = $handler;
        return true;
    }

    /**
     * initializes handlers for specific actions properties
     * @param string $action Actions - get | set | unset | isset | call | invoke | iterator | toString .
     * @param string $prop the member for which the handler is intended
     * @param callable $handler A handler that will process a specific property action
     * @return bool  Indicates whether a handler is set
     */
    public function initProp(string $action, string $prop, callable $handler): bool
    {

        if (!array_key_exists($action, $this->properties) || array_key_exists($prop, $this->properties[$action])) {
            return false;
        }
        if (!($handler instanceof \Closure)) {
            $handler = \Closure::fromCallable($handler);
        }
        $this->properties[$action][$prop] = $handler;
        return true;
    }

    /**
     * Get action.
     * runs handler for the property with the 'get' action
     * @param object|string $target observable object/class
     * @param string $prop
     * @param ProxyInterface $proxy
     * @return mixed
     */
    protected function & runGet($target, string $prop, ProxyInterface $proxy)
    {
        $action = 'get';
        if (array_key_exists($prop, $this->properties[$action])) {
            $answer=& $this->properties[$action][$prop]($target, $prop, $proxy);
        } else if ($this->$action !== null) {
            $answer=& ($this->$action)($target, $prop, $proxy);
        } else {
            $answer=& TStaticMethods::static_run($action, $target, $prop, null, $proxy);
        }
        return $answer;
    }

    /**
     * Set action
     * runs handler for the property with the 'set' action
     * @param object|string $target observable object/class
     * @param string $prop member of object /class
     * @param mixed $value
     * @param ProxyInterface $proxy
     * @return mixed
     */
    protected function runSet($target, string $prop, $value, ProxyInterface $proxy): void
    {
        $action = 'set';
        if (array_key_exists($prop, $this->properties[$action])) {
            $this->properties[$action][$prop]($target, $prop, $value, $proxy);
        } else if ($this->$action !== null) {
            ($this->$action)($target, $prop, $value, $proxy);
        } else {
            TStaticMethods::static_run($action, $target, $prop, $value, $proxy);
        }
    }

    /**
     * Isset action
     * runs handler for the property with the 'isset' action
     * @param object|string $target observable object/class
     * @param string $prop member of object /class
     * @param ProxyInterface $proxy
     * @return bool
     */
    protected function runIsset($target, string $prop, ProxyInterface $proxy): bool
    {
        $action = 'isset';
        if (array_key_exists($prop, $this->properties[$action])) {
            return $this->properties[$action][$prop]($target, $prop, $proxy);
        } else if ($this->$action !== null) {
            return ($this->$action)($target, $prop, $proxy);
        }
        return TStaticMethods::static_run($action, $target, $prop, null, $proxy);
    }

    /**
     * Unset action
     * runs handler for the property with the 'unset' action
     * @param object|string $target observable object/class
     * @param string $prop member of object /class
     * @param ProxyInterface $proxy
     * @return void
     */
    protected function runUnset($target, string $prop, ProxyInterface $proxy): void
    {
        $action = 'unset';
        if (array_key_exists($prop, $this->properties[$action])) {
            $this->properties[$action][$prop]($target, $prop, $proxy);
        } else if ($this->$action !== null) {
            ($this->$action)($target, $prop, $proxy);
        } else {
            TStaticMethods::static_run($action, $target, $prop, null, $proxy);
        }
    }

    /**
     * Call Action.
     * runs handler for the property with the 'call' action
     * by default the member in target must be a method
     * @param object|string $target observable object/class
     * @param string $prop member of object / class
     * @param array $arguments Execution arguments
     * @param ProxyInterface $proxy
     * @return mixed
     * @throws \Exception
     */
    protected function &runCall($target, string $prop, array $arguments, ProxyInterface $proxy)
    {
        $action = 'call';
        if (array_key_exists($prop, $this->properties[$action])) {
            $answer =& $this->properties[$action][$prop]($target, $prop, $arguments, $proxy);;
        } else if ($this->$action !== null) {
            $answer =& ($this->$action)($target, $prop, $arguments, $proxy);
        } else {
            $answer =& TStaticMethods::static_run($action, $target, $prop, $arguments, $proxy); 
        }
        return $answer;
    }

    /**
     * Invoke action.
     * invoke object / class
     * @param object|string $target observable object/class
     * @param array $arguments
     * @param ProxyInterface $proxy
     * @return mixed
     * @throws \Exception
     */
    protected function &runInvoke($target, array $arguments, ProxyInterface $proxy)
    {
        $action = 'invoke';
        if ($this->$action !== null) {
            $answer= & ($this->$action)($target, $arguments, $proxy);
        } else {
            $answer=& TStaticMethods::static_run($action, $target, null, $arguments, $proxy);
        }
        return $answer;
    }

    /**
     * ToString action.
     * to string object or class
     * @param object|string $target observable object/class
     * @param ProxyInterface $proxy
     * @return string
     * @throws \Exception
     */
    protected function runToString($target, ProxyInterface $proxy): string
    {
        $action = 'toString';
        if ($this->$action !== null) {
            return ($this->$action)($target, $proxy);
        }
        return TStaticMethods::static_run($action, $target, null, null, $proxy);
    }

    /**
     * Iterator action.
     * @param object|string $target observable object/class
     * @param ProxyInterface $proxy
     * @return \Traversable
     * @throws \Exception
     */
    protected function runIterator($target, ProxyInterface $proxy): \Traversable
    {
        if ($this->iterator !== null) {
            return ($this->iterator)($target, $proxy);
        }
        return TStaticMethods::static_run('iterator', $target, null, null, $proxy);
    }

    public static function  &static_run(string $action, $target, ?string $prop, $value_or_arguments, ProxyInterface $proxy)
    {

    }
}