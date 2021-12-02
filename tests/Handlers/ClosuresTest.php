<?php

namespace Alpa\Tools\ProxyObject\Tests;

use PHPUnit\Framework\TestCase;
use Alpa\Tools\ProxyObject\Proxy;
use Alpa\Tools\ProxyObject\Handlers\Closures;

class ClosuresTest extends TestCase
{
    public static array $fixtures = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $wrapInstance = new class() extends Closures {
            public array $properties = [
                'get' => [],
                'set' => [],
                'isset' => [],
                'unset' => [],
                'call' => [],
            ];
            public ?\Closure $get = null;
            public ?\Closure $set = null;
            public ?\Closure $unset = null;
            public ?\Closure $isset = null;
            public ?\Closure $call = null;
            public ?\Closure $iterator = null;
            public ?\Closure $invoke = null;

            public function runGet($target, string $prop, Proxy $proxy)
            {
                return parent::runGet($target, $prop, $proxy);
            }

            public function runSet($target, string $prop, $value, Proxy $proxy): void
            {
                parent::runSet($target, $prop, $value, $proxy);
            }

            public function runIsset($target, string $prop, Proxy $proxy): bool
            {
                return parent::runIsset($target, $prop, $proxy);
            }

            public function runUnset($target, string $prop, Proxy $proxy): void
            {
                parent::runUnset($target, $prop, $proxy);
            }

            public function runCall($target, string $prop, array $arguments, Proxy $proxy)
            {
                return parent::runCall($target, $prop, $arguments, $proxy);
            }
            public function runInvoke($target,array $arguments, Proxy $proxy)
            {
                return parent::runInvoke($target, $arguments, $proxy);
            }
            public function runToString($target, Proxy $proxy):string
            {
                return parent::runToString($target, $proxy);
            }
            public function runIterator($target, Proxy $proxy): \Traversable
            {
                return parent::runIterator($target, $proxy);
            }
        };
        self::$fixtures = [
            'wrap_data' => [
                'class' => get_class($wrapInstance),
                'instance' => $wrapInstance,
                'emptyProxyInstance' => (new \ReflectionClass(Proxy::class))->newInstanceWithoutConstructor()
            ]
        ];
    }

    public function test_init()
    {
        $class = self::$fixtures['wrap_data']['class'];
        $handlers = new $class();
        $handler = function ($target, $name) {

        };
        $check = $handlers->init('bad_action', $handler);
        $this->assertTrue(!$check && $handlers->get === null);
        $check = $handlers->init('get', $handler);
        $this->assertTrue($check && $handlers->get === $handler);
        $check = $handlers->init('set', $handler);
        $this->assertTrue($check && $handlers->set === $handler);
        $check = $handlers->init('isset', $handler);
        $this->assertTrue($check && $handlers->isset === $handler);
        $check = $handlers->init('unset', $handler);
        $this->assertTrue($check && $handlers->unset === $handler);
        $check = $handlers->init('call', $handler);
        $this->assertTrue($check && $handlers->call === $handler);
        $check = $handlers->init('iterator', $handler);
        $this->assertTrue($check && $handlers->iterator === $handler);

        $handler2 = function ($target, $name) {

        };
        $check = $handlers->init('get', $handler2);
        $this->assertTrue(!$check && $handlers->get !== $handler2 && $handlers->get === $handler);
    }

    public function test_initProp()
    {
        $class = self::$fixtures['wrap_data']['class'];
        $handlers = new $class();
        $handler = function ($target, $name) {

        };
        $check = $handlers->initProp('bad_action', 'prop', $handler);
        $this->assertTrue(!$check && !array_key_exists('bad_action', $handlers->properties));
        $check = $handlers->initProp('get', 'prop', $handler);
        $this->assertTrue($check && $handlers->properties['get']['prop'] === $handler);
        $check = $handlers->initProp('set', 'prop', $handler);
        $this->assertTrue($check && $handlers->properties['set']['prop'] === $handler);
        $check = $handlers->initProp('unset', 'prop', $handler);
        $this->assertTrue($check && $handlers->properties['unset']['prop'] === $handler);
        $check = $handlers->initProp('isset', 'prop', $handler);
        $this->assertTrue($check && $handlers->properties['isset']['prop'] === $handler);
        $check = $handlers->initProp('call', 'prop', $handler);
        $this->assertTrue($check && $handlers->properties['call']['prop'] === $handler);

        $handler2 = function ($target, $name) {

        };
        $check = $handlers->initProp('get', 'prop', $handler2);
        $this->assertTrue(!$check && $handlers->properties['get']['prop'] !== $handler2 && $handlers->properties['get']['prop'] === $handler);
    }

    public function test_runGet()
    {
        $class = self::$fixtures['wrap_data']['class'];
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $self = $this;
        $testTarget = (object)[];
        $handler = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2');
            return 100;
        };
        $handlerProp = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop');
            return 101;
        };
        try {
            $result = $instance->runGet($testTarget, 'prop', $emptyProxy);
            $self->assertFalse(true);
        } catch (\Exception $e) {
            $self->assertTrue(true);
        }
        $instance->init('get', $handler);
        $instance->initProp('get', 'prop', $handlerProp);
        $result1 = $instance->runGet($testTarget, 'prop', $emptyProxy);
        $result2 = $instance->runGet($testTarget, 'prop2', $emptyProxy);
        $self->assertTrue($result1 === 101 && $result2 === 100);

    }

    public function test_runGet_for_class()
    {
        $class = get_class(new class() {
            public static $prop = 100;
            public static $prop2 = 101;

        });
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        static::assertTrue($handlers->runGet($class, 'prop', $emptyProxy) === 100 && $handlers->runGet($class, 'prop2', $emptyProxy) === 101);

        $handler = function ($target, $name, $value) use ($self, $class) {
            $self->assertTrue($class === $target);
            return $target::$$name + 1;
        };
        $handlerProp = function ($target, $name, $value) use ($self, $class) {
            $self->assertTrue($class === $target);
            return $target::$$name + 2;
        };
        $handlers->init('get', $handler);
        static::assertTrue($handlers->runGet($class, 'prop', $emptyProxy) === 101 && $handlers->runGet($class, 'prop2', $emptyProxy) === 102);
        $handlers->initProp('get', 'prop', $handlerProp);
        static::assertTrue($handlers->runGet($class, 'prop', $emptyProxy) === 102 && $handlers->runGet($class, 'prop2', $emptyProxy) === 102);
    }

    public function test_runSet()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $self = $this;
        $testTarget = (object)[];
        $handler = function ($target, $name, $value) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2' && $value === 100);
            $target->$name = 100;
        };
        $handlerProp = function ($target, $name, $value) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop' && $value === 101);
            $target->$name = 101;
        };
        $instance->runSet($testTarget, 'default', 99, $emptyProxy);
        $instance->init('set', $handler);
        $instance->initProp('set', 'prop', $handlerProp);
        $instance->runSet($testTarget, 'prop', 101, $emptyProxy);
        $instance->runSet($testTarget, 'prop2', 100, $emptyProxy);
        $self->assertTrue($testTarget->default === 99 && $testTarget->prop === 101 && $testTarget->prop2 === 100);
    }

    public function test_runSet_for_class()
    {
        $class = get_class(new class() {
            public static $prop = 100;
            public static $prop2 = 101;

        });
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        $handlers->runSet($class, 'prop', 200, $emptyProxy);
        $handlers->runSet($class, 'prop2', 201, $emptyProxy);
        static::assertTrue($class::$prop === 200 && $class::$prop2 === 201);
        $handler = function ($target, $name, $value) use ($self, $class) {
            $target::$$name = $value + 1;
        };
        $handlerProp = function ($target, $name, $value) use ($self, $class) {
            $target::$$name = $value + 2;
        };
        $handlers->init('set', $handler);
        $handlers->runSet($class, 'prop', 200, $emptyProxy);
        $handlers->runSet($class, 'prop2', 201, $emptyProxy);
        static::assertTrue($class::$prop === 201 && $class::$prop2 === 202);
        $handlers->initProp('set', 'prop', $handlerProp);
        $handlers->runSet($class, 'prop', 200, $emptyProxy);
        $handlers->runSet($class, 'prop2', 201, $emptyProxy);
        static::assertTrue($class::$prop === 202 && $class::$prop2 === 202);
        try {
            $handlers->runSet($class, 'no_prop', 201, $emptyProxy);
            static::assertTrue(false);
        } catch (\Error $e) {
            static::assertTrue(true);
        }
    }

    public function test_runIsset()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $self = $this;
        $testTarget = (object)['default' => 99];
        $handler = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2');
            return false;
        };
        $handlerProp = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop');
            return true;
        };
        $self->assertTrue($instance->runIsset($testTarget, 'default', $emptyProxy));
        $instance->init('isset', $handler);
        $instance->initProp('isset', 'prop', $handlerProp);
        $result1 = $instance->runIsset($testTarget, 'prop', $emptyProxy);
        $result2 = $instance->runIsset($testTarget, 'prop2', $emptyProxy);
        $self->assertTrue($result1 && !$result2);
    }

    public function test_runIsset_for_class()
    {
        $class = get_class(new class() {
            public static $prop = 100;
            public static $prop2 = 101;

        });
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        static::assertTrue(
            $handlers->runIsset($class, 'prop', $emptyProxy) &&
            $handlers->runIsset($class, 'prop2', $emptyProxy) &&
            !$handlers->runIsset($class, 'no_prop', $emptyProxy)
        );
        $handler = function ($target, $name, $value) use ($self, $class) {
            return true;
        };
        $handlerProp = function ($target, $name, $value) use ($self, $class) {
            return false;
        };
        $handlers->init('isset', $handler);
        static::assertTrue(
            $handlers->runIsset($class, 'prop', $emptyProxy) &&
            $handlers->runIsset($class, 'no_prop', $emptyProxy)
        );
        $handlers->initProp('isset', 'prop', $handlerProp);
        static::assertTrue(
            !$handlers->runIsset($class, 'prop', $emptyProxy) &&
            $handlers->runIsset($class, 'no_prop', $emptyProxy)
        );
    }

    public function test_runUnset()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $self = $this;
        $testTarget = (object)['default' => 99, 'prop' => 100, 'prop2' => 200];
        $handler = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2');
            unset($target->$name);
        };
        $handlerProp = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop');
        };
        $instance->runUnset($testTarget, 'default', $emptyProxy);
        $instance->init('unset', $handler);
        $instance->initProp('unset', 'prop', $handlerProp);
        $instance->runUnset($testTarget, 'prop', $emptyProxy);
        $instance->runUnset($testTarget, 'prop2', $emptyProxy);
        $self->assertTrue(
            property_exists($testTarget, 'prop')
            && !property_exists($testTarget, 'prop2')
            && !property_exists($testTarget, 'default')
        );
    }

    public function test_runUnset_for_class()
    {
        $class = get_class(new class() {
            public static $prop = 100;
            public static $prop2 = 101;

        });
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        try {
            $handlers->runUnset($class, 'prop', $emptyProxy);
            static::assertTrue(false);
        } catch (\Throwable $e) {
            static::assertTrue(true);
        }

        $handler = function ($target, $name, $value) use ($self, $class) {

        };
        $handlerProp = function ($target, $name, $value) use ($self, $class) {
            unset($target::$$name);
        };
        $handlers->init('unset', $handler);
        $handlers->runUnset($class, 'prop', $emptyProxy);
        static::assertTrue(isset($class::$prop));
        $handlers->initProp('unset', 'prop', $handlerProp);
        try {
            $handlers->runUnset($class, 'prop', $emptyProxy);
            static::assertTrue(false);
        } catch (\Throwable $e) {
            static::assertTrue(true);
        }
    }

    public function test_runCall()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $self = $this;
        $testTarget = (object)['default' => 99, 'prop' => 100, 'prop2' => 200];
        try {
            $instance->runCall($testTarget, 'prop', [1], $emptyProxy);
            static:: assertTrue(false);
        } catch (\Throwable $e) {
            static:: assertTrue(true);
        }
        $handler = function ($target, $name, $args) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2' && $args[0] === 'test');
            return 'hello';
        };
        $handlerProp = function ($target, $name, $args) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop' && $args[0] === 'test');
            return 'bay';
        };
        $instance->init('call', $handler);
        $instance->initProp('call', 'prop', $handlerProp);
        $result1 = $instance->runCall($testTarget, 'prop', ['test'], $emptyProxy);
        $result2 = $instance->runCall($testTarget, 'prop2', ['test'], $emptyProxy);
        $self->assertTrue(
            $result1 === 'bay'
            && $result2 === 'hello'
        );
    }

    public function test_runCall_for_class()
    {
        $class = get_class(new class() {
            public static $prop = 100;

            public static function method()
            {
                return 102;
            }
        });
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        try {
            $handlers->runCall($class, 'prop', [1], $emptyProxy);
            static:: assertTrue(false);
        } catch (\Throwable $e) {
            static:: assertTrue(true);
        }
        static:: assertTrue($handlers->runCall($class, 'method', [], $emptyProxy) === 102);
        $handler = function ($target, $name, $args) {
            return 'hello';
        };
        $handlerProp = function ($target, $name, $args) {
            return 'bay';
        };
        $handlers->init('call', $handler);
        static:: assertTrue(
            $handlers->runCall($class, 'method', [], $emptyProxy) === 'hello' &&
            $handlers->runCall($class, 'prop', [], $emptyProxy) === 'hello'
        );
        $handlers->initProp('call', 'prop', $handlerProp);
        static:: assertTrue(
            $handlers->runCall($class, 'method', [], $emptyProxy) === 'hello' &&
            $handlers->runCall($class, 'prop', [], $emptyProxy) === 'bay'
        );
    }

    public function test_runIterator()
    {
        $default_itr = new \ArrayIterator([]);
        $obj = new class ($default_itr) implements \IteratorAggregate {
            public function __construct($itr)
            {
                $this->itr = $itr;
            }

            public function getIterator()
            {
                return $this->itr;
            }
        };
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $self = $this;
        $obj2 = (object)['test' => 'test'];
        $itr = $instance->runIterator($obj, $emptyProxy);
        $self->assertTrue($itr === $default_itr);
        $itr = $instance->runIterator($obj2, $emptyProxy);
        $self->assertTrue($itr instanceof \ArrayIterator);
        $handler = function ($target) use ($self, $obj2, $default_itr) {
            $self->assertTrue($obj2 === $target);
            return $default_itr;
        };
        $instance->init('iterator', $handler);
        $itr = $instance->runIterator($obj2, $emptyProxy);
        $self->assertTrue($itr === $default_itr);
    }
    public function test_runInvoke()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        $handler = function ($target, $args) {
            return $args[0]+1;
        };
        $instance->init('invoke', $handler);
        static ::assertTrue($instance->runInvoke((object)[],[1],$emptyProxy)===2);
        
        
    }
    public function test_runInvoke_for_class()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        $handler = function ($target, $args) {
            return $args[0]+1;
        };
        $instance->init('invoke', $handler);
        static ::assertTrue($instance->runInvoke(get_class(new class () {}),[1],$emptyProxy)===2);
    }
    public function test_runIterator_for_class()
    {
        $class = get_class(new class() {
            public static $prop = 100;
            public static $prop2 = 101;

        });
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        $proxy = new Proxy($class, $handlers);
        $check = false;
        foreach ($proxy as $key => $value) {
            static::assertTrue(isset($class::$$key) && $class::$$key === $value);
            $check = true;
        }
        static::assertTrue($check);
        $default_itr = new \ArrayIterator([]);
        $handler = function ($target) use ($default_itr) {
            return $default_itr;
        };
        $handlers->init('iterator', $handler);
        static::assertTrue($handlers->runIterator($class, $emptyProxy) === $default_itr);
    }

    public function test_runToString_for_instance_and_class()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $emptyProxy = self::$fixtures['wrap_data']['emptyProxyInstance'];
        $HandlerClass = self::$fixtures['wrap_data']['class'];
        $handlers = new $HandlerClass();
        $self = $this;
        $handler = function ($target) 
        {
            return 'HELLO';
        };
        $inst=new class () {};
        $class=get_class($inst);
        try {
            $instance->runToStirng($inst,$emptyProxy);
            static ::assertTrue(false);
        } catch(\Throwable $e){
            static ::assertTrue(true);
        }
        static ::assertTrue($instance->runToString($class,$emptyProxy)===$class);
        static ::assertTrue($instance->runToString(new class (){public function __toString(){return 'hello';}},$emptyProxy)==='hello');
        $instance->init('toString', $handler);
        static ::assertTrue($instance->runToString($inst,$emptyProxy)==='HELLO');
    }
}