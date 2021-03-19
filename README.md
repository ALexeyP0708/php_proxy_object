# ProxyObject

The component creates a proxy object for the observed object.  
Action handlers (getter | setter | caller | isseter | unseter | iterator) are assigned for each member of the observable object .  
A similar principle is implemented in javascript through the Proxy constructor.  
When accessing a member of an object, through the proxy object, the assigned handler for the specific action will be invoked.  
Where the component can be applied:  
- mediator for data validation;  
- access to private data of an object through reflection;  
- dynamic data formation, and generation of other properties;  
- dynamic data requests, for example from a database;  
- other options.  

## Install

The component has not yet been loaded into packagist.
To install to your project via the composer, add the following settings to the composer.json file of your project
```json
"require": {
	"alpa/proxy_object": "^1.0.0"
},
"repositories": [  
	{  
        "type": "git",  
        "url": "https://github.com/ALexeyP0708/php_proxy_object.git"  
	}  
]
```
then run the command
`composer update`

## Getting start

example 1:
```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers{
    protected static function get(object $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtoupper($target->$prop):$target->$prop;        
    }
    protected static function get_test(object $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtolower($target->$prop):$target->$prop;        
    }
};
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=MyHandlers::getProxy($obj); 
// or $proxy=new Proxy($obj,MyHandlers::class); 
echo $proxy->test; // hello
echo $proxy->other;// BAY
```
example 2:

```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
$handlers = new Handlers();
$handlers->init('get',function($target,$name,$proxy){
	return is_string($target->$prop)?strtoupper($target->$prop):$target->$prop;      
});
$handlers->initProp('get','test',function($target,$name,$proxy){
	return is_string($target->$prop)?strtolower($target->$prop):$target->$prop;       
});

$proxy=new Proxy($obj,$handlers); 
echo $proxy->test; // hello
echo $proxy->other;// BAY
```

example 3:

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
## Create handlers for Proxy object

There are two ways to write handlers:  
- dynamic writing of handlers through closure functions.  
- static writing of handlers through class declaration.  

There are two types of handlers:  
- a handler for a specific member of an object;  
- handler for all members of the object;  

If no action handler is assigned to a member, then an action handler for all members is applied.
If no action handler is assigned to members, then standard actions will be applied.

The following actions exist when accessing the members of an object:  
- set - member entry;   
- get - member value query;    
- isset - member check;    
- unset - member delete;  
- call - member call;  
- iterator - assigning an iterator when iterating over the members of an object.  

### Dynamic writing of handlers through closure functions  

Example in the constructor  
```php
<?php
$handlers=new \Alpa\ProxyObject\Handlers([
    // handler for members query
    'get'=>function($target,$prop,$proxy){},
    // handler for  members entry
    'set'=>function($target,$prop,$value,$proxy):void{},
    // handler for entry members
    'unset'=>function($target,$prop,$proxy):void{},
    //  handler to check if members exist
    'isset'=>function($target,$prop,$proxy):bool{},
    // handler for delete members
    'iterator'=>function($target,$prop,$proxy):\Traversable{},
]);
```
Handlers can be assigned outside of the constructor.  
An example of assigning handlers via the Handlers :: init method  
```php
<?php

$handlers=new \Alpa\ProxyObject\Handlers();
$handlers->init('get',function($target,$name,$proxy){});
$handlers->init('set',function($target,$name,$value,$proxy):void{});
$handlers->init('unset',function($target,$prop,$proxy):void{});
$handlers->init('isset',function($target,$prop,$proxy):bool{});
$handlers->init('iterator',function($target,$prop,$proxy):\Traversable{});
```

An example of assigning handlers for a specific property  

```php
<?php
$handlers=new \Alpa\ProxyObject\Handlers([],[
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
    ]       
]);
```

or

```php
<?php
$handlers=new \Alpa\ProxyObject\Handlers();
$handlers->initProp('get','prop',function ($target,$name,$proxy):mixed{});
$handlers->initProp('set','prop',function ($target,$name,$value,$proxy):void{});
$handlers->initProp('unset','prop',function ($target,$name,$proxy):void{});
$handlers->initProp('isset','prop',function ($target,$name,$proxy):bool{});
```

### Static writing of handlers through class declaration.

Class declaration in which static methods will be handlers.  
```php
<?php

use Alpa\ProxyObject\HandlersClass;
class MyHandlers extends HandlersClass
{
    
};
```
You can declare the following static methods as handlers:
- get -  member value query;  
- get_{$name_property} - value query of a member named $name_property;  
- set -  member value entry;    
- set_{$name_property} - value entry of a member named $name_property;  
- isset - checking is  set member;  
- isset_{$name_property} - checking is set a member named $name_property;  
- unset - delete a member;  
- unset_{$name_property} - removing a member  named $name_property;  
- call - call member;  
- call_{$name_property} - call  a member  named $name_property;  
- iterator - assigning an iterator to foreach;  

A template for creating action handlers for all members of an object.
```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers{
    /**
    * member value query handler
    * @param object $target - observable object
    * @param string $prop - object member name  
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return mixed - it is necessary to return the result
    */
    public static function get (object $target,string $prop,$value_or_args=null,Proxy $proxy)
    {
       return $target->$prop;
    }
    /**
    * member value entry handler 
    * @param object $target - observable object
    * @param string $prop - object member name 
    * @param mixed $value_or_args - value to assign
    * @param Proxy $proxy - the proxy object from which the method is called
    * @return void 
    */
    public static function set (object $target,string $prop,$value_or_args,Proxy $proxy):void
    {
      
    }
    /**
    * checking is  set member handler
    * @param object $target - observable object
    * @param string $prop - object member name 
    * @param null $value_or_args - irrelevant 
    * @param Proxy $proxy  the proxy object from which the method is called
    * @return bool
    */
    public static function isset (object $target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return isset($target->$prop);
    }
    
    /**
    * member delete handler 
    * @param object $target - observable object
    * @param string $prop -  object member name 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return void
    */
    public static function unset (object $target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        unset($target->$prop);
    }    
    
    /**
    * Member call handler
    * @param object $target - observable object
    * @param string $prop -  object member name 
    * @param array $value_or_args - arguments to the called function.
    * @param Proxy $proxy the proxy object from which the method is called
    * @return mixed
    */
    public static function call (object $target,string $prop,array $value_or_args =[],Proxy $proxy)
    {
        
    }
    
    /**
    * creates an iterator for foreach
    * @param object $target - observable object
    * @param null $prop - irrelevant 
    * @param null $value_or_args -irrelevant 
    * @param Proxy $proxy the proxy object from which the method is called
    * @return \Traversable
    */
    public static function iterator  (object $target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        
    }
    
};
```

Assignment of handlers for a specific member follows the pattern of assigning handlers for all properties.  
Example:
```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\Handlers;
class MyHandlers extends Handlers{
    protected static function get(object $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtoupper($target->$prop):$target->$prop;        
    }
    protected static function get_test(object $target,string $prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtolower($target->$prop):$target->$prop;        
    }
};
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=MyHandlers::getProxy($obj); 
// or $proxy=new Proxy($obj,MyHandlers::class); 

echo $proxy->test; // hello
echo $proxy->other;// BAY
```

## Creating handler classes

The constructor of the `Alpa \ ProxyObject \ Proxy` class can accept as handlers any object or class that implements the` Alpa \ ProxyObject \ HandlersContract` interface.
```php
<?php
use Alpa\ProxyObject\HandlersContract;
class MyHandlersClass implements  HandlersContract
{
	public function run(string $action,object $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
	{
	}
	public static  function static_run(string $action,object $target,?string $prop=null,$value_or_arguments=null,Proxy $proxy)
	{
	}
}
$target=(object)[];
$proxy = new Proxy ($target,MyHandlersClass::class);
$handlers=new MyHandlersClass ();
$proxy = new Proxy ($target,$handlers);
```

 
