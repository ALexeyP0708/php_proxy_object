<?php

namespace Alpa\ProxyObject\Tests;

use \Alpa\ProxyObject\Handlers;

class HandlersTest extends \PHPUnit\Framework\TestCase
{
    public static array $fixtures = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $wrapInstance = new class() extends Handlers {
            public array $properties = [
                'get' => [],
                'set' => [],
                'isset' => [],
                'unset' => [],
                'call' => []
            ];
            public ?\Closure $get = null;
            public ?\Closure $set = null;
            public ?\Closure $unset = null;
            public ?\Closure $isset = null;
            public ?\Closure $call = null;
            public ?\Closure $iterator = null;
        };
        self::$fixtures = [
            'wrap_data' => [
                'class' => get_class($wrapInstance),
                'instance' => $wrapInstance
            ]
        ];
    }

   /* public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }*/

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
            $result = $instance->runGet($testTarget, 'prop');
            $self->assertFalse(true);
        } catch (\Exception $e) {
            $self->assertTrue(true);
        }
        $instance->init('get', $handler);
        $instance->initProp('get', 'prop', $handlerProp);
        $result1 = $instance->runGet($testTarget, 'prop');
        $result2 = $instance->runGet($testTarget, 'prop2');
        $self->assertTrue($result1 === 101 && $result2 === 100);
    }

    public function test_runSet()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $self = $this;
        $testTarget = (object)[];
        $handler = function ($target, $name, $value) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2' && $value===100);
            $target->$name = 100;
        };
        $handlerProp = function ($target, $name, $value) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop' && $value===101);
            $target->$name = 101;
        };
        $instance->runSet($testTarget, 'default', 99);
        $instance->init('set', $handler);
        $instance->initProp('set', 'prop', $handlerProp);
        $instance->runSet($testTarget, 'prop', 101);
        $instance->runSet($testTarget, 'prop2', 100);
        $self->assertTrue($testTarget->default === 99 && $testTarget->prop === 101 && $testTarget->prop2 === 100);
    }

    public function test_runIsset()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
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
        $self->assertTrue($instance->runIsset($testTarget, 'default'));
        $instance->init('isset', $handler);
        $instance->initProp('isset', 'prop', $handlerProp);
        $result1 = $instance->runIsset($testTarget, 'prop');
        $result2 = $instance->runIsset($testTarget, 'prop2', null);
        $self->assertTrue($result1 && !$result2);
    }

    public function test_runUnset()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $self = $this;
        $testTarget = (object)['default' => 99, 'prop' => 100, 'prop2' => 200];
        $handler = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2');
            unset($target->$name);
        };
        $handlerProp = function ($target, $name) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop');
        };
        $instance->runUnset($testTarget, 'default');
        $instance->init('unset', $handler);
        $instance->initProp('unset', 'prop', $handlerProp);
        $instance->runUnset($testTarget, 'prop');
        $instance->runUnset($testTarget, 'prop2');
        $self->assertTrue(
            property_exists($testTarget, 'prop')
            && !property_exists($testTarget, 'prop2')
            && !property_exists($testTarget, 'default')
        );
    }

    public function test_runCall()
    {
        $instance = self::$fixtures['wrap_data']['instance'];
        $self = $this;
        $testTarget = (object)['default' => 99, 'prop' => 100, 'prop2' => 200];
        $handler = function ($target, $name,$args) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop2' && $args[0]==='test');
            return 'hello';
        };
        $handlerProp = function ($target, $name, $args) use ($self, $testTarget) {
            $self->assertTrue($testTarget === $target && $name === 'prop' && $args[0]==='test');
            return 'bay';
        };
        $instance->init('call', $handler);
        $instance->initProp('call', 'prop', $handlerProp);
        $result1=$instance->runCall($testTarget, 'prop',['test']);
        $result2=$instance->runCall($testTarget, 'prop2',['test']);
        $self->assertTrue(
            $result1==='bay'
            && $result2==='hello'
        );
    }

    public function test_runIterator()
    {
        $default_itr=new \ArrayIterator([]);
        $obj=new class ($default_itr) implements \IteratorAggregate
        {
            public function __construct($itr)
            {
                $this->itr=$itr;
            }
            public function getIterator()
            {
                return $this->itr;
            }
        };
        $instance = self::$fixtures['wrap_data']['instance'];
        $self = $this;
        $obj2=(object)['test'=>'test'];
        $itr=$instance->runIterator($obj);
        $self->assertTrue( $itr===$default_itr);
        $itr=$instance->runIterator($obj2);
        $self->assertTrue($itr instanceof \ArrayIterator);
        $handler=function ($target) use ($self, $obj2,$default_itr) {
            $self->assertTrue($obj2 === $target );
            return $default_itr;
        };
        $instance->init('iterator',$handler);
        $itr=$instance->runIterator($obj2);
        $self->assertTrue($itr ===$default_itr);
    }
}