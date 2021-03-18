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
- other options  .

The following actions exist when accessing the members of an object:  
set - member entry  
get - member value query  
isset - member check  
unset - member delete  
iterator - assigning an iterator when iterating over the members of an object.

Usage example:

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
## Create handlers
If no action handler is assigned to a property, then an action handler for all properties is applied.
If no action handler is assigned to properties, then standard actions will be applied.
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
## Создание прокси и обработчики в классе
Обьявляем класс, в котором статические методы будут являться обработчиками.
```php
<?php

use Alpa\ProxyObject\HandlersClass;
class MyHandlers extends HandlersClass{
    
};
```
Вы можете объвить следующие статические методы в качестве обработчиков:
get -  запроса значения члена.
get_{$name_property} -  запроса значения члена  с именем $name_property
set -  запись значения члена.
set_{$name_property} - запись значения члена с именем $name_property
isset - проверка наличия члена.
isset_{$name_property} - проверка наличия члена с именем $name_property
unset - удаления члена.
unset_{$name_property} - удаления члена с именем $name_property
call - вызов члена
call_{$name_property} - вызов члена с именем $name_property
iterator - назначения итератора для foreach

Обьявления методов  для всех дествий однотипно
```php
<?php
use Alpa\ProxyObject\Proxy;
use Alpa\ProxyObject\HandlersClass;
class MyHandlers extends HandlersClass{
    /**
    * Обработчик запроса значения члена 
    * @param object $target - наблюдаемый обьект 
    * @param string $prop - имя члена обьекта
    * @param null $value_or_args - не имеет значения
    * @param Proxy $proxy - прокси обьект с которого вызван метод
    * @return mixed - необходимо возвращать результат
    */
    public static function get (object $target,string $prop,$value_or_args=null,Proxy $proxy)
    {
       return $target->$prop;
    }
    /**
    * Обработчик присвоения значения члену 
    * @param object $target - наблюдаемый обьект  
    * @param string $prop - имя члена обьекта
    * @param mixed $value_or_args - значение для присвоения
    * @param Proxy $proxy - прокси обьект с которого вызван метод
    * @return void 
    */
    public static function set (object $target,string $prop,$value_or_args,Proxy $proxy):void
    {
      
    }
    /**
    * Обработчик проверки члена
    * @param object $target - наблюдаемый обьект 
    * @param string $prop - имя члена обьекта
    * @param null $value_or_args не имеет значения
    * @param Proxy $proxy  прокси обьект с которого вызван метод
    * @return bool
    */
    public static function isset (object $target,string $prop,$value_or_args=null,Proxy $proxy):bool
    {
        return isset($target->$prop);
    }
    
    /**
    * Обработчик удаления члена
    * @param object $target - наблюдаемый обьект 
    * @param string $prop - имя члена обьекта
    * @param null $value_or_args не имеет значения
    * @param Proxy $proxy прокси обьект с которого вызван метод
    * @return void
    */
    public static function unset (object $target,string $prop,$value_or_args=null,Proxy $proxy):void
    {
        unset($target->$prop);
    }    
    
    /**
    * Обработчик вызова члена
    * @param object $target - наблюдаемый обьект 
    * @param string $prop - имя члена обьекта
    * @param array $value_or_args аргументы для вызываемой функции.
    * @param Proxy $proxy прокси обьект с которого вызван метод
    * @return mixed
    */
    public static function call (object $target,string $prop,array $value_or_args =[],Proxy $proxy)
    {
        
    }
    
    /**
    * формирует итератор для foreach
    * @param object $target - наблюдаемый обьект 
    * @param null $prop - не имеет значения
    * @param null $value_or_args не имеет значения
    * @param Proxy $proxy прокси обьект с которого вызван метод
    * @return \Traversable
    */
    public static function iterator  (object $target,$prop=null,$value_or_args=null,Proxy $proxy):\Traversable
    {
        
    }
    // далее назначение обработчиков для конкретного члена происходит по шаблону назначения обработчиков всех свойств.
};
```


Пример
```php
<?php
class MyHandlers extends HandlersClass{
    protected static function get($target,$prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtoupper($target->$prop):$target->$prop;        
    }
    protected static function get_test($target,$prop,$val_or_args=null,Proxy $proxy)
    {
        return is_string($target->$prop)?strtolower($target->$prop):$target->$prop;        
    }
};
$obj=(object)[
    'test'=>'HELLO',
    'other'=>'bay'
];
$proxy=MyHandlers::getProxy();
echo $proxy->test; // hello
echo $proxy->other;// BAY
```

