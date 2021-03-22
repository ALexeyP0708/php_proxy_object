<?php


namespace Alpa\ProxyObject\Handlers;


use Alpa\ProxyObject\Proxy;

class Closures implements IContract
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

    public function run(string $action, $target, ?string $prop = null, $value_or_arguments = null, Proxy $proxy)
    {
        switch ($action) {
            case 'get':
                return $this->runGet($target, $prop, $proxy);
            case 'set':
                $this->runSet($target, $prop, $value_or_arguments, $proxy);
                break;
            case 'isset':
                return $this->runIsset($target, $prop, $proxy);
            case 'unset':
                $this->runUnset($target, $prop, $proxy);
                break;
            case 'call':
                return $this->runCall($target, $prop, $value_or_arguments, $proxy);
            case 'invoke':
                return $this->runInvoke($target, $value_or_arguments, $proxy);
            case 'toString':
                return $this->runToString($target, $proxy);
            case 'iterator':
                return $this->runIterator($target, $proxy);
        }
    }

    /**
     * initializes handlers for specific actions (get | set | unset | isset | call | invoke | iterator)
     * @param string $action get | set | unset | isset | call The action for which you want to install the handler
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
     * @param string $action get | set | unset | isset | call The action for which you want to install the handler
     * @param string $prop the property for which the handler is intended
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
     * runs handler for the property with the 'get' action
     * @param object|string $target
     * @param string $prop
     * @param Proxy $proxy
     * @return mixed
     */
    protected function runGet($target, string $prop, Proxy $proxy)
    {
        $action = 'get';
        if (array_key_exists($prop, $this->properties[$action])) {
            return $this->properties[$action][$prop]($target, $prop, $proxy);
        } else  if ($this->$action !== null) {
            return ($this->$action)($target, $prop, $proxy);
        }
        return TStaticMethods::static_run($action,$target,$prop,null,$proxy);
    }

    /**
     * runs handler for the property with the 'set' action
     * @param object|string $target
     * @param string $prop
     * @param $value
     * @param Proxy $proxy
     * @return mixed
     */
    protected function runSet($target, string $prop, $value, Proxy $proxy): void
    {
        $action = 'set';
        if (array_key_exists($prop, $this->properties[$action])) {
            $this->properties[$action][$prop]($target, $prop, $value, $proxy);
        } else if ($this->$action !== null) {
            ($this->$action)($target, $prop, $value, $proxy);
        } else {
            TStaticMethods::static_run($action,$target,$prop,$value,$proxy);
        }       
    }

    /**
     * runs handler for the property with the 'isset' action
     * @param object|string $target
     * @param string $prop
     * @param Proxy $proxy
     * @return bool
     */
    protected function runIsset($target, string $prop, Proxy $proxy): bool
    {
        $action = 'isset';
        if (array_key_exists($prop, $this->properties[$action])) {
            return $this->properties[$action][$prop]($target, $prop, $proxy);
        } else if ($this->$action !== null) {
            return ($this->$action)($target, $prop, $proxy);
        }
        return TStaticMethods::static_run($action,$target,$prop,null,$proxy);
    }

    /**
     * runs handler for the property with the 'unset' action
     * @param object|string $target
     * @param string $prop
     * @param Proxy|null $proxy
     * @return void
     */
    protected function runUnset($target, string $prop, Proxy $proxy): void
    {
        $action = 'unset';
        if (array_key_exists($prop, $this->properties[$action])) {
            $this->properties[$action][$prop]($target, $prop, $proxy);
        } else if ($this->$action !== null) {
            ($this->$action)($target, $prop, $proxy);
        } else  {
            TStaticMethods::static_run($action,$target,$prop,null,$proxy);
        }
    }

    /**
     * runs handler for the property with the 'call' action
     * by default the member in target must be a method
     * @param object|string $target
     * @param string $prop
     * @param array $arguments
     * @param Proxy $proxy
     * @return mixed
     */
    protected function runCall($target, string $prop, array $arguments, Proxy $proxy)
    {
        $action = 'call';
        if (array_key_exists($prop, $this->properties[$action])) {
            return $this->properties[$action][$prop]($target, $prop, $arguments, $proxy);
        } else if ($this->$action !== null) {
            return ($this->$action)($target, $prop, $arguments, $proxy);
        }
        return TStaticMethods::static_run($action,$target,$prop,$arguments,$proxy);
    }    
    
    /**
     * invoke object
     * @param object|string $target
     * @param array $arguments
     * @param Proxy $proxy
     * @return mixed
     */
    protected function runInvoke($target, array $arguments, Proxy $proxy)
    {
        $action = 'invoke';
        if ($this->$action !== null) {
            return ($this->$action)($target, $arguments, $proxy);
        }
        return TStaticMethods::static_run($action,$target,null,$arguments,$proxy);
    }  
    /**
     * to string object|class
     * @param object|string $target
     * @param Proxy $proxy
     * @return string
     */
    protected function runToString($target, Proxy $proxy):string
    {
        $action = 'toString';
        if ($this->$action !== null) {
            return ($this->$action)($target, $proxy);
        }
        return TStaticMethods::static_run($action,$target,null,null,$proxy);
    }

    /**
     * @param $target
     * @param Proxy|null $proxy
     * @return \Traversable
     * @throws \Exception
     */
    protected function runIterator($target, Proxy $proxy): \Traversable
    {
        if ($this->iterator !== null) {
            return ($this->iterator)($target, $proxy);
        }
        return TStaticMethods::static_run('iterator',$target,null,null,$proxy);
    }

    public static function static_run(string $action,  $target, ?string $prop = null, $value_or_arguments = null, Proxy $proxy)
    {

    }
}