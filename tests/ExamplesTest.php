<?php


namespace Alpa\ProxyObject\Tests;

use Alpa\Tools\ProxyObject\Handlers\Instance;
use Alpa\Tools\ProxyObject\Proxy;
use Alpa\Tools\ProxyObject\Handlers\Closures;

use Alpa\Tools\ProxyObject\ProxyInterface;
use PHPUnit\Framework\TestCase;

class ExamplesTest extends TestCase
{
    public static array $fixtures = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $handlers = new Closures([
            'get' => function & ($target, $name, ProxyInterface $proxy) {
                $name = '_' . $name;
                return $target->$name;
            },
            'set' => function ($target, $name, $value, ProxyInterface $proxy): void {
                $name = '_' . $name;
                $target->$name = $value;
            },
            'isset' => function ($target, $name, ProxyInterface $proxy): bool {
                $name = '_' . $name;
                return property_exists($target, $name);
            },
            'unset' => function ($target, $name, ProxyInterface $proxy): void {
                $name = '_' . $name;
                unset($target->$name);
            },
            'iterator' => function ($target, ProxyInterface $proxy) {
                return new class($target, $proxy) implements \Iterator {
                    private object $target;
                    private Proxy $proxy;
                    private array $keys = [];
                    private int $key = 0;

                    public function __construct(object $target, ProxyInterface $proxy)
                    {
                        $this->target = $target;
                        $this->proxy = $proxy;
                        $this->rewind();
                    }

                    public function current()
                    {
                        $prop = $this->key();
                        return $prop !== null ? $this->proxy->$prop : null;
                    }

                    public function key()
                    {
                        $prop = $this->keys[$this->key] ?? null;
                        return $prop !== null ? ltrim($prop, '_') : null;
                    }

                    public function next(): void
                    {
                        $this->key++;
                    }

                    public function rewind()
                    {
                        $this->key = 0;

                        $this->keys = array_keys(get_object_vars($this->target));
                    }

                    public function valid(): bool
                    {
                        $prop = $this->key();
                        return $prop !== null &&
                            isset($this->proxy->$prop);
                    }
                };
            }
        ]);
        static::$fixtures = [
            'handlers' => $handlers
        ];

    }

    public function test_example_1()
    {
        $target = (object)['_test' => 'test'];
        $proxy = new Proxy($target, static::$fixtures['handlers']);
        $this->assertTrue($proxy->test === 'test');
        $this->assertTrue(isset($proxy->test) && !isset($proxy->_test));
        $proxy->test = 'change value';
        $this->assertTrue($target->_test === 'change value');
        unset($proxy->test);
        $this->assertTrue(!isset($target->_test));
    }

    public static function test_example_2()
    {
        $target = (object)['_test' => 'test', '_test2' => 'test'];
        $proxy = new Proxy($target, static::$fixtures['handlers']);
        foreach ($proxy as $key => $value) {
            static::assertTrue(property_exists($target, '_' . $key) && $target->{'_' . $key} === $value);
        }
    }

    public static function test_example_3()
    {
        $inst = new class () extends Instance {
            protected static function &static_get( $target, string $prop, $val_or_args, ProxyInterface $proxy)
            {
                $answer=is_string($target->$prop) ? strtoupper($target->$prop) : $target->$prop;
                return $answer;
            }

            protected static function & static_get_test( $target, string $prop, $val_or_args, ProxyInterface $proxy)
            {
                $answer = is_string($target->$prop) ? strtolower($target->$prop) : $target->$prop;
                return $answer;
            }
        };
        $obj = (object)[
            'test' => 'HELLO',
            'other' => 'bay'
        ];
        $proxy = new Proxy($obj,get_class($inst));
        static::assertTrue($proxy->test === 'hello');
        static::assertTrue($proxy->other === 'BAY');
    }

    public static function test_example_4()
    {
        $inst = new class ('Alex ') extends Instance {
            public function __construct($prefix)
            {
                $this->prefix = $prefix;
            }

            protected function &get( $target, string $prop, $val_or_args, ProxyInterface $proxy)
            {
                $answer=is_string($target->$prop) ? strtoupper($this->prefix . $target->$prop) : $target->$prop;
                return $answer;
            }

            protected function get_test( $target, string $prop, $val_or_args, ProxyInterface $proxy)
            {
                return is_string($target->$prop) ? strtolower($this->prefix . $target->$prop) : $target->$prop;
            }
        };
        $obj = (object)[
            'test' => 'HELLO',
            'other' => 'bay'
        ];
        $proxy = new Proxy($obj,$inst);
        static::assertTrue($proxy->test === 'alex hello');
        static::assertTrue($proxy->other === 'ALEX BAY');
    }
}