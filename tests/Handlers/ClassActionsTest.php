<?php


namespace Alpa\Tools\ProxyObject\Tests;


use Alpa\Tools\ProxyObject\Handlers\InstanceActions;
use Alpa\Tools\ProxyObject\Handlers\StaticActions;
use PHPUnit\Framework\TestCase;


class ClassActionsTest extends TestCase
{
    public static function test_static_actions()
    {
        $actions=new class () extends StaticActions{};
        $target=(object)['hello'=>'hello'];
        $proxy=$actions::proxy($target);
        static::assertTrue($proxy->hello==='hello');
    }
    public static function test_instance_actions()
    {
        $actions=new class () extends InstanceActions{};
        $target=(object)['hello'=>'hello'];
        $proxy=$actions->newProxy($target);
        static::assertTrue($proxy->hello==='hello');
    }
}