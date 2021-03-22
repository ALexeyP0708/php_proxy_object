# ProxyObject
## Warning
### 1
From version 1.0.0 to version 1.1.0 is experimental development, where the API will be subject to modification.  
See the tag description on GitHub for version compatibility.  
When including a component in a project, specify a specific version.  
The description of the api for a specific version can be found in the commit version.  

### 2 

If for some reason you have bugs or other problems, then it is recommended to implement your own handlers classes that fix this problem.
See - [Creating handler classes](#creating-handler-classes)

### 3

The proxy object is a wrapper object that implements magic methods.
By default it is not possible to access protected / private members of an observable object / class.
The default proxy only works with the public members of the observable / class.
If you need the ability to work with protected / private members of an object / class, use Reflector classes in action handlers.

### 4

Not the fact that the use of proxy objects will be fast.
But in any case, this is the optimal solution for
creating complex combinations and in a simple way.

### Versions
template: x.y.z   
- x - version with major changes. Has no backward compatibility   
- y - Versions with modifications t hat do not break backward compatibility   
- z - Versions for adding new features, changing APIs, refactoring and fixing bugs.   
Should not break backward compatibility with version "Y".  



## Description 
The component creates a proxy object for the observed object or class.  
Action handlers (get | set | call | isset | unset | invoke | toString | iterator) are assigned for each member of the observable object or class .   
A similar principle is implemented in javascript through the Proxy constructor.   
When accessing a member of an object, through the proxy object, the assigned handler for the specific action will be invoked.   

Where the component can be applied:
- mediator for data validation;
- access to private data of an object through reflection;
- dynamic data formation, and generation of other properties;
- dynamic data requests, for example from a database;
- other options.

## Install

`composer require alpa/proxy_object:v1.0.8`  - I recommend freezing on version v1.0.8


## Getting started

example 1:

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers\Instance 
{
    protected static function static_get( $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop) ? strtoupper($target->$prop) : $target->$prop;        
    }
    protected static function static_get_test( $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop) ? strtolower($target->$prop) : $target->$prop;        
    }
}
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=MyHandlers::proxy($obj); 
// or $proxy=new Proxy($obj,MyHandlers::class); 
echo $proxy->test; // hello
echo $proxy->other;// BAY
```

example 2:

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers\Instance 
{
    public function __construct($prefix)
    {
        $this->prefix=$prefix;
    }
    protected function get( $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop) ? strtoupper($this->prefix.$target->$prop) : $target->$prop;        
    }
    protected function get_test( $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop) ? strtolower($this->prefix.$target->$prop) : $target->$prop;        
    }
}
$inst=new MyHandlers('Alex ');
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=$inst->newProxy($obj);
//or $proxy=$inst::proxy($obj,$inst);
// or $proxy=new Proxy($obj,$inst); 
echo $proxy->test; // alex hello
echo $proxy->other;// ALEX BAY

```

example 3:

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
$handlers = new Handlers\Closures();
$handlers->init('get',function($target,$prop,$proxy){
	return is_string($target->$prop) ? strtoupper($target->$prop) : $target->$prop;      
});
$handlers->initProp('get','test',function($target,$prop,$proxy){
	return is_string($target->$prop) ? strtolower($target->$prop) : $target->$prop;       
});
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=new Proxy($obj,$handlers); 
echo $proxy->test; // hello
echo $proxy->other;// BAY
```

example 4 - 

```php
<?php
use \Alpa\ProxyObject\Proxy;
use \Alpa\ProxyObject\Handlers;
$handlers = new Handlers([
    'get' => function ($target, $name, Proxy $proxy) {
        $name = '_' . $name;
        return $target->$name;
    },
    'set' => function ($target, $name, $value, Proxy $proxy): void {
        $name = '_' . $name;
        $target->$name = $value;
    },
    'isset' => function ($target, $name, Proxy $proxy): bool {
        $name = '_' . $name;
        return property_exists($target,$name) ;
    },
    'unset' => function ($target, $name, Proxy $proxy): void {
        $name = '_' . $name;
        unset($target->$name);
    },
    'iterator' => function ($target, $proxy) {
        return new class($target, $proxy) implements \Iterator {
            private object $target;
            private Proxy $proxy;
            private array $keys = [];
            private int $key = 0;

            public function __construct(object $target, Proxy $proxy)
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
$target=(object)['_test'=>'test'];
$proxy=new Proxy($target,$handlers);

echo $proxy->test;//  get $target->_test value return 'test'
$proxy->test='new value';// set  $target->_test value
echo $proxy->test; // get $target->_test value return 'new value'
echo isset($proxy->test); // isset($target->_test) return true

foreach($proxy as $key=>$value){
    echo $key; // test
    echo $value; // $proxy->test value  => $target->_test value
}

unset($proxy->test); // unset($target->_test) 
echo key_exists($target,'_test'); // return false;

```

## Definitions
Definitions:
- member(s) - properties and methods of an object or class
- action (s) -Actions that can be applied to members of a class or object(`set|get|isset|unset|call`).
  As well  actions that are applied to a object or class  (`invoke | toString |iterator`).
  Sometimes the definition of "action" is understood as an action handler.
- handler(s) or action(s) handlers  - A function or method that handles actions
- proxy - an object with declared magic methods, which will pass actions through itself to the members of the observable object or class.
  The proxy object is a wrapper object that implements magic methods.The default proxy only works with the public members of the observable / class.

## Create handlers for Proxy object

There are two ways to write handlers:
- dynamic writing of handlers through closure functions.
- writing of handlers through class declaration.

There are two types of handlers:
- a handler for a specific member of an object;
- handler for all members of the object;

If action handler is no assigned to a member, then an action handler for all members is applied.   
If  action handler is no assigned to members, then standard actions will be applied.

The following actions exist when accessing the members of an object:
- set - member value entry;
- get - member value query;
- isset - member check  ;
- unset - member delete;
- call - member call;
- invoke - invoke object or class;
- toString - converting object or class to string;
- iterator - assigning an iterator when iterating over the members of an object.


### Dynamic writing of handlers through closure functions

Example in the constructor

```php
<?php
$handlers=new \Alpa\ProxyObject\Handlers\Closures([
    // handler for members query
    'get'=>function($target,$prop,$proxy){},
    // handler for  members entry
    'set'=>function($target,$prop,$value,$proxy):void{},
    // handler for entry members
    'unset'=>function($target,$prop,$proxy):void{},
    //  handler to check if members exist
    'isset'=>function($target,$prop,$proxy):bool{},
    //  handler to call members
    'call'=>function($target,$prop,$args,$proxy){},
    // handler for invoke object or class 
    'invoke'=>function($target,array $args,$proxy){},
    // handler for toString object or class 
    'toString'=>function($target,$proxy):string {},
    // handler for delete members
    'iterator'=>function($target,$prop,$proxy):\Traversable{},
]);
```

Handlers can be assigned outside of the constructor.  
An example of assigning handlers via the Handlers :: init method

```php
<?php

$handlers=new \Alpa\ProxyObject\Handlers\Closures();
$handlers->init('get',function($target,$name,$proxy){});
$handlers->init('set',function($target,$name,$value,$proxy):void{});
$handlers->init('unset',function($target,$prop,$proxy):void{});
$handlers->init('isset',function($target,$prop,$proxy):bool{});
$handlers->init('call',function($target,$prop, $args,$proxy){});
$handlers->init('invoke',function($target,$args,$proxy){});
$handlers->init('toString',function($target,$proxy){});
$handlers->init('iterator',function($target,$prop,$proxy):\Traversable{});
```

An example of assigning handlers for a specific property

```php
<?php
$handlers=new \Alpa\ProxyObject\Handlers\Closures([],[
    'get'=>[
        'prop'=>function ($target,$name,$proxy):mixed{}
    ],
    'set'=>[
        'prop'=>function ($target,$name,$value,$proxy):void{}  
    ],
    'unset'=>[
         'prop'=>function ($target,$name,$proxy):void{}  
    ] ,
    'isset'=>[
         'prop'=>function ($target,$name,$proxy):bool{}  
    ],
    'call'=>[
         'prop'=>function ($target,$name,$args,$proxy){}  
    ]     
]);
```

or

```php
<?php
$handlers=new \Alpa\ProxyObject\Handlers\Closures();
$handlers->initProp('get','prop',function ($target,$name,$proxy):mixed{});
$handlers->initProp('set','prop',function ($target,$name,$value,$proxy):void{});
$handlers->initProp('unset','prop',function ($target,$name,$proxy):void{});
$handlers->initProp('isset','prop',function ($target,$name,$proxy):bool{});
$handlers->initProp('call','prop',function ($target,$name,$args,$proxy){});
```


### Static writing of handlers through class declaration.

Class declaration in which methods will be handlers.

```php
<?php

use Alpa\ProxyObject\Handlers\Instance;
class MyHandlers extends Instance
{
    
};
```

or 

```php
<?php
// to declare only static actions
use Alpa\ProxyObject\Handlers\StaticActions;
class MyHandlers extends StaticActions
{
    
};
```

or

```php
<?php
// to declare only instance actions
use Alpa\ProxyObject\Handlers\InstanceActions;
class MyHandlers extends InstanceActions
{
    
};
```
You can declare the following instance methods as handlers
(when inheriting classes `Alpa\ProxyObject\Handlers\StaticActions` 
or `Alpa\ProxyObject\Handlers\InstanceActions`
or `Alpa\ProxyObject\Handlers\Instance`)
:
- get - member value query;
- get_{$name_property} - value query of a member named $name_property;
- set - member value entry;
- set_{$name_property} - value entry of a member named $name_property;
- isset - checking is set member;
- isset_{$name_property} - checking is set a member named $name_property;
- unset - delete a member;
- unset_{$name_property} - removing a member named $name_property;
- call - call member;
- call_{$name_property} - call a member named $name_property;
- invoke - invoke object or class;
- toString - converting object or class to string; 
- iterator - assigning an iterator to foreach;


You can declare the following static methods as handlers :
(when inheriting class  `Alpa\ProxyObject\Handlers\Instance`)
- static_get - member value query;
- static_get_{$name_property} - value query of a member named $name_property;
- static_set - member value entry;
- static_set_{$name_property} - value entry of a member named $name_property;
- static_isset - checking is set member;
- static_isset_{$name_property} - checking is set a member named $name_property;
- static_unset - delete a member;
- static_unset_{$name_property} - removing a member named $name_property;
- static_call - call member;
- static_call_{$name_property} - call a member named $name_property;
- static_invoke - invoke object or class;
- static_toString - converting object or class to string;
- static_iterator - assigning an iterator to foreach;


A templates for creating action handlers for all members of an object.

Template where static actions and actions for an instance are declared
```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers\Instance
{
    /**
    * member value query handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name  
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return mixed - it is necessary to return the result
    */
    protected function get ($target,string $prop,$value_or_args=null,Proxy $proxy)
    {
       return parent::get($target,$prop,$value_or_args,$proxy);
    }    

    /**
    * member value entry handler 
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param mixed $value_or_args - value to assign
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return void 
    */
    protected function set ( $target,string $prop,$value_or_args,Proxy $proxy):void
    {
        parent::set($target,$prop,$value_or_args,$proxy);
    }
    /**
    * checking is  set member handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy  the proxy object from which the method is called
    * @return bool
    */
    protected function isset ($target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return parent::isset($target,$prop,$value_or_args,$proxy);
    }
    
    /**
    * member delete handler 
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return void
    */
    protected function unset ($target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        parent::unset($target,$prop,$value_or_args,$proxy);
    }    
    
    /**
    * Member call handler
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param array $value_or_args - arguments to the called function.
    * @param Proxy $proxy the proxy object from which the method is called
    * @return mixed
    */
    protected function call ($target,string $prop,array $value_or_args =[],Proxy $proxy)
    {
        return parent::call($target,$prop,$value_or_args,$proxy);
    }
    
    /**
     * invoke object
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -irrelevant 
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    protected  function invoke($target, $prop=null, array $value_or_args = [], Proxy $proxy)
    {
        return parent::invoke($target,$prop,$value_or_args,$proxy);
    }
    
    /**
     * converting to string object or class
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -irrelevant 
     * @param null $value_or_args -irrelevant 
     * @param Proxy $proxy the proxy object from which the method is called
     * @return string
     */
    protected  function toString($target, $prop=null,  $value_or_args = null, Proxy $proxy):string
    {
        return parent::toString($target,$prop,$value_or_args,$proxy);
    }
    
    /**
    * creates an iterator for foreach
    * @param object|string $target - observable object or class.
    * @param null $prop - irrelevant 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return \Traversable
    */
    protected function iterator  ($target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        return parent::iterator($target,$prop,$value_or_args,$proxy);
    } 
    
    /**
    * member value query handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name  
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return mixed - it is necessary to return the result
    */
    protected static function static_get ($target,string $prop,$value_or_args=null,Proxy $proxy)
    {
       return  parent::static_get($target,$prop,$value_or_args,$proxy);
    }    
    
    /**
    * member value entry handler 
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param mixed $value_or_args - value to assign
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return void 
    */
    protected static function static_set ($target,string $prop,$value_or_args,Proxy $proxy):void
    {
        parent::static_set($target,$prop,$value_or_args,$proxy);
    }
    /**
    * checking is  set member handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy  the proxy object from which the method is called
    * @return bool
    */
    protected static function static_isset ($target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return parent::static_isset($target,$prop,$value_or_args,$proxy);
    }
    
    /**
    * member delete handler 
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return void
    */
    protected static function static_unset ($target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        parent::static_unset($target,$prop,$value_or_args,$proxy);
    }    
    
    /**
    * Member call handler
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param array $value_or_args - arguments to the called function.
    * @param Proxy $proxy the proxy object from which the method is called
    * @return mixed
    */
    protected static function static_call ($target,string $prop,array $value_or_args =[],Proxy $proxy)
    {
        return parent::static_call($target,$prop,$value_or_args,$proxy);
    }
    
    /**
     * invoke object
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    protected  static function static_invoke($target, $prop=null, array $value_or_args = [], Proxy $proxy)
    {
        return parent::static_invoke($target,$prop,$value_or_args,$proxy);
    }
    /**
     * converting to string object or class
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -irrelevant 
     * @param null $value_or_args -irrelevant 
     * @param Proxy $proxy the proxy object from which the method is called
     * @return string
     */
    protected  static function static_toString($target, $prop=null,  $value_or_args = null, Proxy $proxy):string
    {
        return parent::static_toString($target,$prop,$value_or_args,$proxy);
    }
    
    /**
    * creates an iterator for foreach
    * @param object|string $target - observable object or class.
    * @param null $prop - irrelevant 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return \Traversable
    */
    protected static function static_iterator  ($target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        return parent::static_iterator($target,$prop,$value_or_args,$proxy);
    }
};
```
Template where only instance actions

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers\InstanceActions
{
    /**
    * member value query handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name  
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return mixed - it is necessary to return the result
    */
    protected function get ($target,string $prop,$value_or_args=null,Proxy $proxy)
    {
       return parent::get($target,$prop,$value_or_args,$proxy);
    }    

    /**
    * member value entry handler 
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param mixed $value_or_args - value to assign
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return void 
    */
    protected function set ( $target,string $prop,$value_or_args,Proxy $proxy):void
    {
        parent::set($target,$prop,$value_or_args,$proxy);
    }
    /**
    * checking is  set member handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy  the proxy object from which the method is called
    * @return bool
    */
    protected function isset ($target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return parent::isset($target,$prop,$value_or_args,$proxy);
    }
    
    /**
    * member delete handler 
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return void
    */
    protected function unset ($target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        parent::unset($target,$prop,$value_or_args,$proxy);
    }    
    
    /**
    * Member call handler
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param array $value_or_args - arguments to the called function.
    * @param Proxy $proxy the proxy object from which the method is called
    * @return mixed
    */
    protected function call ($target,string $prop,array $value_or_args =[],Proxy $proxy)
    {
        return parent::call($target,$prop,$value_or_args,$proxy);
    }
    
    /**
     * invoke object
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    protected function invoke($target, $prop=null, array $value_or_args = [], Proxy $proxy)
    {
        return parent::invoke($target,$prop,$value_or_args,$proxy);
    }
    /**
     * converting to string object or class
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -irrelevant 
     * @param null $value_or_args -irrelevant 
     * @param Proxy $proxy the proxy object from which the method is called
     * @return string
     */
    protected  function toString($target, $prop=null,  $value_or_args = null, Proxy $proxy):string
    {
        return parent::toString($target,$prop,$value_or_args,$proxy);
    }
    /**
    * creates an iterator for foreach
    * @param object|string $target - observable object or class.
    * @param null $prop - irrelevant 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return \Traversable
    */
    protected function iterator  ($target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        return parent::iterator($target,$prop,$value_or_args,$proxy);
    } 
};
```
Template where only static actions

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers\StaticActions
{
    /**
    * member value query handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name  
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return mixed - it is necessary to return the result
    */
    protected static function get ($target,string $prop,$value_or_args=null,Proxy $proxy)
    {
       return parent::get($target,$prop,$value_or_args,$proxy);
    }    

    /**
    * member value entry handler 
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param mixed $value_or_args - value to assign
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return void 
    */
    protected static function set ( $target,string $prop,$value_or_args,Proxy $proxy):void
    {
        parent::set($target,$prop,$value_or_args,$proxy);
    }
    /**
    * checking is  set member handler
    * @param object|string $target - observable object or class.
    * @param string $prop - object member name 
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy  the proxy object from which the method is called
    * @return bool
    */
    protected static function isset ($target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return parent::isset($target,$prop,$value_or_args,$proxy);
    }
    
    /**
    * member delete handler 
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return void
    */
    public static function unset ($target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        parent::unset($target,$prop,$value_or_args,$proxy);
    }    
    
    /**
    * Member call handler
    * @param object|string $target - observable object or class.
    * @param string $prop -  object member name 
    * @param array $value_or_args - arguments to the called function.
    * @param Proxy $proxy the proxy object from which the method is called
    * @return mixed
    */
    protected static function call ($target,string $prop,array $value_or_args =[],Proxy $proxy)
    {
        return parent::call($target,$prop,$value_or_args,$proxy);
    }
    /**
     * invoke object
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -  object member name
     * @param array $value_or_args - arguments to the called function.
     * @param Proxy $proxy the proxy object from which the method is called
     * @return mixed
     */
    protected static function invoke($target, $prop=null, array $value_or_args = [], Proxy $proxy)
    {
        return parent::static_invoke($target,$prop,$value_or_args,$proxy);
    } 
     
     /**
     * converting to string object or class
     * by default the member in target must be a method
     * @param object|string $target - observable object
     * @param null $prop -irrelevant 
     * @param null $value_or_args -irrelevant 
     * @param Proxy $proxy the proxy object from which the method is called
     * @return string
     */
    protected static function toString($target, $prop=null,  $value_or_args = null, Proxy $proxy):string
    {
        return parent::toString($target,$prop,$value_or_args,$proxy);
    }  
    /**
    * creates an iterator for foreach
    * @param object|string $target - observable object or class.
    * @param null $prop - irrelevant 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return \Traversable
    */
    
    protected static function iterator  ($target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        return parent::iterator($target,$prop,$value_or_args,$proxy);
    } 
};
```


Action handlers for a specific member are created similar to action handlers for all properties.
The exceptions are the "invoke"? "toString" and "iterator" actions. they only apply to the observable object or class.

Example:

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers\Instance {
    protected static function static_get($target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtoupper($target->$prop):$target->$prop;        
    }
    protected static function static_get_test($target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        // $prop==='test';
        return is_string($target->$prop)?strtolower($target->$prop):$target->$prop;        
    }
};
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=MyHandlers::proxy($obj); 
// or $proxy=new Proxy($obj,MyHandlers::class); 

echo $proxy->test; // hello
echo $proxy->other;// BAY
```
## Proxying class static members

In addition to proxying the members of an object, it is possible to proxy static members of a class.
To do this, instead of an object, you will need to specify the class

```php
<?php
use Alpa\ProxyObject\Handlers\Instance; 
class MyClass{
	public static $prop1='Hello';
	public static $prop2='bay';
	public static function method(int $arg)
	{
		return $arg+1;
	}
}
class MyHandlers extends Instance{}
$proxy=MyHandlers::proxy(MyClass::class);
// or $proxy = new Proxy(MyClass::class,MyHandlers::class);
echo $proxy->prop1;// 'Hello';
$proxy->prop2='BAY';
echo MyClass::$prop2;// 'BAY';
echo isset($proxy->prop2);// true;
echo isset($proxy->no_prop);// false;
$proxy->prop2='test';// Errror:Cannot set new static class property
unset($proxy->prop2);// Errror:Cannot unset  static class property
$proxy->prop2();// Errror:By default, you cannot call the property. But you can set a handler on the call action and the properties will be called according to the handler logic.
echo $proxy->method(1);// return 2
foreach($proxy as $key=>$value){
	echo $key." && ".$value;
	// prop1 && Hello;
	// prop2 && BAY;
}
```



## Creating handler classes

The constructor of the `Alpa \ ProxyObject \ Proxy` class can accept as handlers any object or class that implements the ` Alpa \ ProxyObject \ Handlers \ IContract` interface.

```php
<?php
use Alpa\ProxyObject\Handlers\IContract;
use Alpa\ProxyObject\Proxy;
class MyHandlersClass implements  IContract
{
	public function run(string $action, $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
	{
	}
	public static  function static_run(string $action, $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
	{
	}
}
$target=(object)[];
$proxy = new Proxy ($target,MyHandlersClass::class);
$handlers=new MyHandlersClass ();
$proxy = new Proxy ($target,$handlers);
```

For each action (set | get | isset | unset | call | invoke | toString | iterator) you will need to implement working code.  

If for some reason you have bugs or other problems, then it is recommended to implement your own handlers classes that fix this problem.

## Difficulties

For a member of a proxy object, it doesn't make sense to apply checks such as `property_exists` or` method_exists` or similar, since they will be applied directly to the proxy object. Therefore, when working with a proxy, always use the `isset` check.  If you have complex logic where you need to check both properties and methods, then it is recommended to separate the logic for working with properties and the logic for working with methods.

```php
<?php
	use Alpa\ProxyObject\Proxy;
	use Alpa\ProxyObject\Hanclers\Instance;
	class MyHandlers extends Instance
	{
		protected bool $is_methods=false;
		public function __construct(bool $is_methods=false){
			$this->is_methods=$is_methods;
		}
		protected function isset ( $target,string $prop,$val=null,Proxy $proxy):bool
		{
			if($this->is_methods){
				return method_exists($target,$prop);
			}
			return property_exists($target,$prop);
		}
	}
	class TargetClass
	{
		public $property='hello';
		public function method(){}
	}
	$inst=new TargetClass();
	$proxyProps=MyHandlers::proxy($inst, new  MyHandlers());
	$proxyMethods=MyHandlers::proxy($inst, new  MyHandlers(true));  

```  
