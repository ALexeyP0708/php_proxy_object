<?php


namespace Alpa\Tools\ProxyObject\Handlers;

use Alpa\Tools\ProxyObject\ProxyAbstract;
use Alpa\Tools\ProxyObject\ProxyInterface;

interface ActionsInterface
{
    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param mixed|array|null $value_or_arguments
     * @param ProxyInterface $proxy
     * @return mixed
     */
    public function &run(string $action, $target, ?string $prop, $value_or_arguments, ProxyInterface $proxy);

    /**
     * @param string $action
     * @param object|string $target
     * @param string|null $prop
     * @param mixed|array|null $value_or_args
     * @param ProxyInterface $proxy
     * @return mixed
     */
    public static function &static_run(string $action, $target, ?string $prop, $value_or_args, ProxyInterface $proxy);
}