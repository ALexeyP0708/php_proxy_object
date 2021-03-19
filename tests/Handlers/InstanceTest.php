<?php
namespace Alpa\ProxyObject\Tests;

use Alpa\ProxyObject\Handlers\Instance;
use Alpa\ProxyObject\Proxy;
use \PHPUnit\Framework\TestCase;

class InstanceTest extends TestCase
{
    public static function test_default_static_action()
    {
        $inst=new class() extends Instance {};
        $HandlersClass= get_class($inst);
        $target = (object)['test'=>'test'];
        $proxy = $HandlersClass::proxy($target);
        static::assertTrue($proxy->test==='test' && $target->test===$proxy->test);
        $proxy->test='success';
        static::assertTrue($target->test==='success');
        static::assertTrue(isset($proxy->test) && !isset($proxy->no_prop));
        foreach($proxy as $key=>$value){
            static::assertTrue(isset($target->$key) && $target->$key===$value);
        }
        unset($proxy->test);
        static::assertTrue(!isset($target->test));
    }

    public static function test_core_static_action()
    {
        $inst=new class() extends Instance {
            public static function static_get(object $target,string $prop,$val=null,Proxy $proxy)
            {
                return isset($target->$prop)?$target->$prop.'_':'empty';
            }
            public static function static_set(object $target,string $prop,$val,Proxy $proxy):void
            {
                $target->$prop='_'.$val;
            }
            public static function static_isset(object $target,string $prop,$val=null,Proxy $proxy=null):bool
            {
                return true;
            }
            public static function static_unset(object $target,string $prop,$val=null,Proxy $proxy=null):void
            {

            }
            public static function static_call(object $target,string $prop, array $args=[],Proxy $proxy=null)
            {
                return $args[0];
            }
            public static function static_iterator(object $target,$prop=null,$val=null,Proxy $proxy=null):\Traversable
            {
                $props=array_keys(get_object_vars($target));
                return new class($props,$proxy) implements \Iterator{
                    protected array $props=[];
                    protected Proxy $proxy;
                    protected int $key=0;
                    public function __construct (array $props,Proxy $proxy)
                    {
                        $this->props=$props;
                        $this->proxy=$proxy;
                    }
                    public function rewind()
                    {
                        $this->key=0;
                    }
                    public function key()
                    {
                        return  $this->props[$this->key];
                    }
                    public function current()
                    {
                        $prop=$this->key();
                        return $this->proxy->$prop;
                    }
                    public function next()
                    {
                        $this->key++;
                    }
                    public function valid():bool
                    {
                        return isset($this->props[$this->key]);
                    }
                };
            }
        };
        $HandlersClass= get_class($inst);
        $target = (object)['test'=>'test'];
        $proxy = $HandlersClass::proxy($target);
        static::assertTrue($proxy->test==='test_' && $target->test!==$proxy->test);
        static::assertTrue($proxy->test2==='empty');
        $proxy->test='success';
        static::assertTrue($target->test==='_success');
        $proxy->test2='success2';
        static::assertTrue($target->test2==='_success2');
        static::assertTrue(isset($proxy->test) && isset($proxy->no_prop));
        foreach($proxy as $key=>$value){
            static::assertTrue(isset($target->$key) && $target->$key.'_'===$value);
        }
        unset($proxy->test);
        static::assertTrue(isset($target->test));
        static::assertTrue($proxy->test('q')==='q' && $proxy->no_test('z')==='z');
    }

    public static function test_props_static_action()
    {
        $inst=new class() extends Instance
        {
            public static function static_get_test(object $target,string $prop,$val=null,Proxy $proxy)
            {
                return $target->$prop.'_';
            }
            public static function static_set_test(object $target,string $prop,$val,Proxy $proxy)
            {
                $target->test='_'.$val;
            }
            public static function static_isset_test(object $target,string $prop,$val=null,Proxy $proxy)
            {
                return false;
            }
            public static function static_unset_test(object $target,string $prop,$val=null,Proxy $proxy=null)
            {
                
            }
            public static function static_call_test(object $target,string $prop,$args=[],Proxy $proxy=null)
            {
                return $args[0];
            }
        };
        $HandlersClass= get_class($inst);
        $target = (object)['test'=>'test','test2'=>'test2'];
        $proxy = $HandlersClass::proxy($target);
        static::assertTrue($proxy->test==='test_' && $target->test!==$proxy->test);
        static::assertTrue($proxy->test2==='test2' && $target->test2===$proxy->test2);
        $proxy->test='success';
        static::assertTrue($target->test==='_success');
        $proxy->test2='success2';
        static::assertTrue($target->test2==='success2');
        static::assertTrue(!isset($proxy->test) && isset($proxy->test2));
        unset($proxy->test);
        unset($proxy->test2);
        static::assertTrue(isset($target->test) && !isset($target->test2));
        static::assertTrue($proxy->test('q')==='q');
    }

    public static function test_default_instance_action()
    {
        $inst=new class() extends Instance {};
        $target = (object)['test'=>'test'];
        $proxy = $inst->newProxy($target);
        static::assertTrue($proxy->test==='test' && $target->test===$proxy->test);
        $proxy->test='success';
        static::assertTrue($target->test==='success');
        static::assertTrue(isset($proxy->test) && !isset($proxy->no_prop));
        foreach($proxy as $key=>$value){
            static::assertTrue(isset($target->$key) && $target->$key===$value);
        }
        unset($proxy->test);
        static::assertTrue(!isset($target->test));
    }
    public static function test_core_instance_action()
    {
        $inst=new class() extends Instance {
            public function get(object $target,string $prop,$val=null,Proxy $proxy)
            {
                return isset($target->$prop)?$target->$prop.'_':'empty';
            }
            public function set(object $target,string $prop,$val,Proxy $proxy):void
            {
                $target->$prop='_'.$val;
            }
            public function isset(object $target,string $prop,$val=null,Proxy $proxy):bool
            {
                return true;
            }
            public function unset(object $target,string $prop,$val=null,Proxy $proxy):void
            {

            }
            public function call(object $target,string $prop, array $args=[],Proxy $proxy)
            {
                return $args[0];
            }
            public function iterator(object $target,$prop=null,$val=null,Proxy $proxy):\Traversable
            {
                $props=array_keys(get_object_vars($target));
                return new class($props,$proxy) implements \Iterator{
                    protected array $props=[];
                    protected Proxy $proxy;
                    protected int $key=0;
                    public function __construct (array $props,Proxy $proxy)
                    {
                        $this->props=$props;
                        $this->proxy=$proxy;
                    }
                    public function rewind()
                    {
                        $this->key=0;
                    }
                    public function key()
                    {
                        return  $this->props[$this->key];
                    }
                    public function current()
                    {
                        $prop=$this->key();
                        return $this->proxy->$prop;
                    }
                    public function next()
                    {
                        $this->key++;
                    }
                    public function valid():bool
                    {
                        return isset($this->props[$this->key]);
                    }
                };
            }
        };
        $target = (object)['test'=>'test'];
        $proxy = $inst->newProxy($target);
        static::assertTrue($proxy->test==='test_' && $target->test!==$proxy->test);
        static::assertTrue($proxy->test2==='empty');
        $proxy->test='success';
        static::assertTrue($target->test==='_success');
        $proxy->test2='success2';
        static::assertTrue($target->test2==='_success2');
        static::assertTrue(isset($proxy->test) && isset($proxy->no_prop));
        foreach($proxy as $key=>$value){
            static::assertTrue(isset($target->$key) && $target->$key.'_'===$value);
        }
        unset($proxy->test);
        static::assertTrue(isset($target->test));
        static::assertTrue($proxy->test('q')==='q' && $proxy->no_test('z')==='z');
    }
    public static function test_props_instance_action()
    {
        $inst=new class() extends Instance
        {
            public function get_test(object $target,string $prop,$val=null,Proxy $proxy)
            {
                return $target->$prop.'_';
            }
            public function set_test(object $target,string $prop,$val,Proxy $proxy)
            {
                $target->test='_'.$val;
            }
            public function isset_test(object $target,string $prop,$val=null,Proxy $proxy)
            {
                return false;
            }
            public function unset_test(object $target,string $prop,$val=null,Proxy $proxy)
            {
                
            }
            public  function call_test(object $target,string $prop,$args=[],Proxy $proxy)
            {
                return $args[0];
            }
        };
        $target = (object)['test'=>'test','test2'=>'test2'];
        $proxy = $inst->newProxy($target);
        static::assertTrue($proxy->test==='test_' && $target->test!==$proxy->test);
        static::assertTrue($proxy->test2==='test2' && $target->test2===$proxy->test2);
        $proxy->test='success';
        static::assertTrue($target->test==='_success');
        $proxy->test2='success2';
        static::assertTrue($target->test2==='success2');
        static::assertTrue(!isset($proxy->test) && isset($proxy->test2));
        unset($proxy->test);
        unset($proxy->test2);
        static::assertTrue(isset($target->test) && !isset($target->test2));
        static::assertTrue($proxy->test('q')==='q');
    }
}