<?php

namespace Alpa\ProxyObject\Tests;

use Alpa\ProxyObject\Handlers\Closures;
use PHPUnit\Framework\TestCase;
use Alpa\ProxyObject\Proxy;

class ProxyTest extends TestCase
{
    public static array $fixtures = [];

    /*   public static function setUpBeforeClass(): void
       {
           parent::setUpBeforeClass();
   
       }
   
       public static function tearDownAfterClass(): void
       {
           parent::tearDownAfterClass();
       }*/
    public function test_proxy()
    {
        $handlers = new Closures();
        $handlers->init('get', function ($target, $name) {
            return 'success';
        });
        $handlers->init('set', function ($target, $name) {
            $target->$name = 'success';
        });
        $handlers->init('isset', function ($target, $name) {
            return true;
        });
        $handlers->init('unset', function ($target, $name) {
            unset($target->$name);
        });

        $handlers->init('unset', function ($target, $name) {
            unset($target->$name);
        });

        $handlers->init('call', function ($target, $name, $args) {
            return $args[0];
        });
        $handlers->init('invoke', function ($target, $args) {
            return $args[0];
        });
        $target = (object)['hello' => 'hello', 'bay' => 'bay'];
        $proxy = new Proxy($target, $handlers);
        $this->assertTrue($proxy->test === 'success' && $proxy->hello === 'success');
        $proxy->test = '211421';
        $this->assertTrue($target->test === 'success');
        $this->assertTrue(isset($proxy->test2));
        unset($proxy->bay);
        $this->assertTrue(!isset($target->bay));
        $this->assertTrue($proxy->hello('success') === 'success');
        $this->assertTrue($proxy('success') === 'success');        
        foreach ($proxy as $key => $value) {
            $this->assertTrue(property_exists($target, $key) && $target->$key === $value);
        }
    }
}