<?php

namespace Alpa\Tools\ProxyObject\Tests;

use Alpa\Tools\ProxyObject\Handlers\Instance;
use Alpa\Tools\ProxyObject\Proxy;
use Alpa\Tools\ProxyObject\ProxyInterface;
use \PHPUnit\Framework\TestCase;

class InstanceTest extends TestCase
{
   
    /**
     * Test the default actions that are assigned by StaticActions class ( static members )
     */
    public static function test_default_static_action()
    {
        $inst = new class() extends Instance {
        };
        $HandlersClass = get_class($inst);
        $target = new class {
            public string $test = 'test';
            public function call():bool
            {
                return true;
            }
        };
        $proxy = new Proxy($target,$HandlersClass);
        static::assertTrue($proxy->test === 'test' && $target->test === $proxy->test, 'test action "get"');
        $proxy->test = 'success';
        static::assertTrue($target->test === 'success', 'test action "set"');
        static::assertTrue(isset($proxy->test) && !isset($proxy->no_prop), 'test action "isset"');
        foreach ($proxy as $key => $value) {
            static::assertTrue(isset($target->$key) && $target->$key === $value, 'test action "iterable"');
        }
        unset($proxy->test);
        static::assertTrue(!isset($target->test), 'test action "unset"');
        self::assertTrue($proxy->call(), 'test action - "call default"');
        $check = false;
        try {
            $proxy();
        } catch (\Throwable $e) {
            $check = true;
        } finally {
            self::assertTrue($check, 'test action - "invoke default"');
        }
        $check = false;
        try {
            $str = $proxy . '';
        } catch (\Throwable $e) {
            $check = true;
        } finally {
            self::assertTrue($check, 'test action - "toString default"');
        }
    }

    /**
     * Test to return by result reference for default actions (StaticActions class) ( static members )
     * Warn: For other variantes, tests are not written since this test is sufficient.
     */
    public static function test_default_static_action_by_reference()
    {
        $target = new class {
            public string $prop = 'hello';

            public function & getProp(): string
            {
                return $this->prop;
            }

            public function setProp(&$var)
            {
                $this->prop =& $var;
            }
            
        };
        $inst = new class() extends Instance {
            public static function & static_invoke($target, $prop, array $args, ProxyInterface $proxy)
            {
                return $target->prop;
            }
        };
        $HandlersClass = get_class($inst);
        $proxy =new Proxy($target,$HandlersClass);
        $proxy->new_prop = 'hello';
        static::assertTrue($target->new_prop === 'hello');
        $var = &$proxy->new_prop;
        $var = 'bay';
        static::assertTrue($target->new_prop === 'bay');
        $check = false;
        unset ($var);
        $var = 'hello';
        // set variable by reference 
        try {
            $proxy->new_prop = &$var;
        } catch (\Error $e) {
            //Error: Cannot assign by reference to overloaded object
            $check = true;
        } finally {
            self::assertTrue($check);
        }
        unset ($var);
        $var = $proxy->getProp();
        $target->prop = 'bay';
        self::assertTrue($var !== $target->prop);
        $var = &$proxy->getProp();
        $target->prop = 'hello';
        self::assertSame($target->prop, $var);
        $var = 'bay';
        self::assertSame($target->prop, $var);
        unset ($var);
        $var = 'HELLO';
        $proxy->setProp($var);
        self::assertSame($target->prop, $var);
        $var = 'hello';
        //Note:None of the arguments of these magic methods can be passed by reference.
        // https://www.php.net/manual/en/language.oop5.overloading.php
        self::assertNotSame($target->prop, $var);
        
        // iterable
        // Variables are not passed by reference in iterations. This is due to the restrictions of the Interator class.
        
        // invoke
        unset($var);
        $var=&$proxy();
        $var='QWER';
        self::assertSame($target->prop, $var);
        unset($var);
        //Note:None of the arguments of these magic methods can be passed by reference.
        // https://www.php.net/manual/en/language.oop5.overloading.php
    }

    /**
     * test of app actions that override the default actions ( static members )
     */
    public static function test_core_static_action()
    {
        $inst = new class() extends Instance {
            public static function & static_get($target, string $prop, $val, ProxyInterface $proxy)
            {
                return isset($target->$prop) ? $target->$prop . '_' : 'empty';
            }

            public static function static_set($target, string $prop, $val, ProxyInterface $proxy): void
            {
                $target->$prop = '_' . $val;
            }

            public static function static_isset($target, string $prop, $val, ProxyInterface $proxy): bool
            {
                return true;
            }

            public static function static_unset($target, string $prop, $val, ProxyInterface $proxy): void
            {

            }

            public static function & static_call($target, string $prop, array $args, ProxyInterface $proxy)
            {
                return $args[0];
            }

            public static function & static_invoke($target, $prop, array $args, ProxyInterface $proxy)
            {
                return $args[0] + 1;
            }

            public static function static_toString($target, $prop, $args, ProxyInterface $proxy): string
            {
                return 'hello';
            }

            public static function static_iterator($target, $prop, $val, ProxyInterface $proxy): \Traversable
            {
                $props = array_keys(get_object_vars($target));
                return new class($props, $proxy) implements \Iterator {
                    protected array $props = [];
                    protected Proxy $proxy;
                    protected int $key = 0;

                    public function __construct(array $props, ProxyInterface $proxy)
                    {
                        $this->props = $props;
                        $this->proxy = $proxy;
                    }

                    public function rewind()
                    {
                        $this->key = 0;
                    }

                    public function key()
                    {
                        return $this->props[$this->key];
                    }

                    public function current()
                    {
                        $prop = $this->key();
                        return $this->proxy->$prop;
                    }

                    public function next()
                    {
                        $this->key++;
                    }

                    public function valid(): bool
                    {
                        return isset($this->props[$this->key]);
                    }
                };
            }
        };
        $HandlersClass = get_class($inst);
        $target = (object)['test' => 'test'];
        $proxy = new Proxy($target,$HandlersClass);
        static::assertTrue($proxy->test === 'test_' && $target->test !== $proxy->test);
        static::assertTrue($proxy->test2 === 'empty');
        $proxy->test = 'success';
        static::assertTrue($target->test === '_success');
        $proxy->test2 = 'success2';
        static::assertTrue($target->test2 === '_success2');
        static::assertTrue(isset($proxy->test) && isset($proxy->no_prop));
        foreach ($proxy as $key => $value) {
            static::assertTrue(isset($target->$key) && $target->$key . '_' === $value);
        }
        unset($proxy->test);
        static::assertTrue(isset($target->test));
        static::assertTrue($proxy->test('q') === 'q' && $proxy->no_test('z') === 'z');
        static::assertTrue($proxy(1) === 2);
        static::assertTrue($proxy . '' === 'hello');
    }
    
    /**
     *  Test the members  actions of app that are assigned ( static members )
     */
    public static function test_props_static_action()
    {
        $inst = new class() extends Instance {
            public static function static_get_test($target, string $prop, $val, ProxyInterface $proxy)
            {
                return $target->$prop . '_';
            }

            public static function static_set_test($target, string $prop, $val, ProxyInterface $proxy)
            {
                $target->test = '_' . $val;
            }

            public static function static_isset_test($target, string $prop, $val, ProxyInterface $proxy)
            {
                return false;
            }

            public static function static_unset_test($target, string $prop, $val, ProxyInterface $proxy)
            {

            }

            public static function static_call_test($target, string $prop, $args = [], ProxyInterface $proxy)
            {
                return $args[0];
            }
        };
        $HandlersClass = get_class($inst);
        $target = (object)['test' => 'test', 'test2' => 'test2'];
        $proxy = new Proxy($target,$HandlersClass);
        static::assertTrue($proxy->test === 'test_' && $target->test !== $proxy->test);
        static::assertTrue($proxy->test2 === 'test2' && $target->test2 === $proxy->test2);
        $proxy->test = 'success';
        static::assertTrue($target->test === '_success');
        $proxy->test2 = 'success2';
        static::assertTrue($target->test2 === 'success2');
        static::assertTrue(!isset($proxy->test) && isset($proxy->test2));
        unset($proxy->test);
        unset($proxy->test2);
        static::assertTrue(isset($target->test) && !isset($target->test2));
        static::assertTrue($proxy->test('q') === 'q');
    }

    /**
     * Test the default actions 
     */
    public static function test_default_instance_action()
    {
        $inst = new class() extends Instance {
        };
        $target = (object)['test' => 'test'];
        $proxy = new Proxy($target,$inst);
        static::assertTrue($proxy->test === 'test' && $target->test === $proxy->test);
        $proxy->test = 'success';
        static::assertTrue($target->test === 'success');
        static::assertTrue(isset($proxy->test) && !isset($proxy->no_prop));
        foreach ($proxy as $key => $value) {
            static::assertTrue(isset($target->$key) && $target->$key === $value);
        }
        unset($proxy->test);
        static::assertTrue(!isset($target->test));
        try {
            $proxy();
            static::assertTrue(false);
        } catch (\Error $e) {
            static::assertTrue(true);
        }
        try {
            $str = $proxy . '';
            static::assertTrue(false);
        } catch (\Error $e) {
            static::assertTrue(true);
        }
    }

    /**
     * test of app actions that override the default actions 
     */
    public static function test_core_instance_action()
    {
        $inst = new class() extends Instance {
            public function & get($target, string $prop, $val, ProxyInterface $proxy)
            {
                return isset($target->$prop) ? $target->$prop . '_' : 'empty';
            }

            public function set($target, string $prop, $val, ProxyInterface $proxy): void
            {
                $target->$prop = '_' . $val;
            }

            public function isset($target, string $prop, $val, ProxyInterface $proxy): bool
            {
                return true;
            }

            public function unset($target, string $prop, $val, ProxyInterface $proxy): void
            {

            }

            public function & call($target, string $prop, array $args, ProxyInterface $proxy)
            {
                return $args[0];
            }

            public function & invoke($target, $prop, array $args, ProxyInterface $proxy)
            {
                return $args[0] + 1;
            }

            public function toString($target, $prop, $args, ProxyInterface $proxy): string
            {
                return 'hello';
            }

            public function iterator($target, $prop, $val, ProxyInterface $proxy): \Traversable
            {
                $props = array_keys(get_object_vars($target));
                return new class($props, $proxy) implements \Iterator {
                    protected array $props = [];
                    protected ProxyInterface $proxy;
                    protected int $key = 0;

                    public function __construct(array $props, ProxyInterface $proxy)
                    {
                        $this->props = $props;
                        $this->proxy = $proxy;
                    }

                    public function rewind()
                    {
                        $this->key = 0;
                    }

                    public function key()
                    {
                        return $this->props[$this->key];
                    }

                    public function current()
                    {
                        $prop = $this->key();
                        return $this->proxy->$prop;
                    }

                    public function next()
                    {
                        $this->key++;
                    }

                    public function valid(): bool
                    {
                        return isset($this->props[$this->key]);
                    }
                };
            }
        };
        $target = (object)['test' => 'test'];
        $proxy = new Proxy($target,$inst);
        static::assertTrue($proxy->test === 'test_' && $target->test !== $proxy->test);
        static::assertTrue($proxy->test2 === 'empty');
        $proxy->test = 'success';
        static::assertTrue($target->test === '_success');
        $proxy->test2 = 'success2';
        static::assertTrue($target->test2 === '_success2');
        static::assertTrue(isset($proxy->test) && isset($proxy->no_prop));
        foreach ($proxy as $key => $value) {
            static::assertTrue(isset($target->$key) && $target->$key . '_' === $value);
        }
        unset($proxy->test);
        static::assertTrue(isset($target->test));
        static::assertTrue($proxy->test('q') === 'q' && $proxy->no_test('z') === 'z');
        static::assertTrue($proxy(1) === 2);
        static::assertTrue($proxy . '' === 'hello');
    }

    /**
     * Test the members  actions of app
     */
    public static function test_props_instance_action()
    {
        $inst = new class() extends Instance {
            public function get_test($target, string $prop, $val, ProxyInterface $proxy)
            {
                return $target->$prop . '_';
            }

            public function set_test($target, string $prop, $val, ProxyInterface $proxy)
            {
                $target->test = '_' . $val;
            }

            public function isset_test($target, string $prop, $val, ProxyInterface $proxy)
            {
                return false;
            }

            public function unset_test($target, string $prop, $val, ProxyInterface $proxy)
            {

            }

            public function call_test($target, string $prop, $args, ProxyInterface $proxy)
            {
                return $args[0];
            }
        };
        $target = (object)['test' => 'test', 'test2' => 'test2'];
        $proxy = new Proxy($target,$inst);
        static::assertTrue($proxy->test === 'test_' && $target->test !== $proxy->test);
        static::assertTrue($proxy->test2 === 'test2' && $target->test2 === $proxy->test2);
        $proxy->test = 'success';
        static::assertTrue($target->test === '_success');
        $proxy->test2 = 'success2';
        static::assertTrue($target->test2 === 'success2');
        static::assertTrue(!isset($proxy->test) && isset($proxy->test2));
        unset($proxy->test);
        unset($proxy->test2);
        static::assertTrue(isset($target->test) && !isset($target->test2));
        static::assertTrue($proxy->test('q') === 'q');
    }

    /**
     * Test the members  actions of app  for static members class
     */
    public static function test_default_action_for_class()
    {
        $inst = new class() extends Instance {
            protected static function & static_invoke($target, $prop, array $value_or_args, ProxyInterface $proxy)
            {
                return $value_or_args[0] + 1;
            }
        };
        $HandlersClass = get_class($inst);
        $target = get_class(new class () {
            public static $prop = 100;
            public static $prop2 = 101;

            public static function method($arg)
            {
                return $arg + 1;
            }
        });
        $proxy = new Proxy($target,$HandlersClass);
        static::assertTrue($proxy->prop === 100);
        try {
            $proxy->no_prop;
            static::assertTrue(false);
        } catch (\Throwable $e) {
            static::assertTrue(true);
        }
        $proxy->prop = 201;
        static::assertTrue($target::$prop === 201);
        try {
            $proxy->no_prop = 201;
            static::assertTrue(false);
        } catch (\Throwable $e) {
            static::assertTrue(true);
        }
        static::assertTrue(isset($proxy->prop));
        static::assertTrue(!isset($proxy->no_prop));
        try {
            unset($proxy->prop);
            static::assertTrue(false);
        } catch (\Throwable $e) {
            static::assertTrue(true);
        }
        try {
            $proxy->prop();
            static::assertTrue(false);
        } catch (\Throwable $e) {
            static::assertTrue(true);
        }
        static::assertTrue($proxy->method(1) === 2);

        $check = false;
        foreach ($proxy as $key => $value) {
            $check = true;
            static::assertTrue(isset($target::$$key) && $target::$$key === $value);
        }
        static::assertTrue($check);
        static::assertTrue($proxy(1) === 2);
        static::assertTrue($proxy . '' === $target);
    }

/*    public static function test_other_proxy_class()
    {
        $inst = new class() extends Instance {
        };
        $target = (object)['test' => 'test'];
        $proxy = $inst::proxy($target, get_class($inst), OtherProxy::class);
        static::assertTrue($proxy instanceof OtherProxy);
        $inst = new class() extends Instance {
            public static $proxyClass = OtherProxy::class;
        };
        $proxy = $inst->newProxy($target);
        static::assertTrue($proxy instanceof OtherProxy);
    }*/
}

/*class OtherProxy extends ProxyAbstract
{
    public function __construct($target, $handlers)
    {
        $this->target = $target;
        $this->handlers = $handlers;
    }
}*/

