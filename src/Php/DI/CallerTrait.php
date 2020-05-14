<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Php;
use ReflectionClass;

trait CallerTrait
{
    public function call($callable, array $params = [])
    {
        if (($container = $this->container ?? $this) && !$container instanceof ContainerInterface) {
            Php::fail('$container property is not an instance of %s', ContainerInterface::class);
        }
        $invoker = $container->has(Invoker::class) ? $invoker = $container->get(Invoker::class) : null;
        if (!$invoker instanceof Invoker) {
            Php::fail('invoker is not an instance of %s', Invoker::class);
        }
        $class = $method = null;
        if (is_string($callable) && ($class = $callable) && strpos($callable, '::')) {
            [$class, $method] = explode('::', $callable);
        }
        if ($class) {
            $obj = $container && $container->has($class) ? $container->get($class) : null;
            if (!$obj) {
                if ($ref = (new ReflectionClass($class))->getConstructor()) {
                    $args = $invoker->getParameters($ref, [], []);
                    $obj = $ref->getDeclaringClass()->newInstanceArgs($args);
                } else {
                    $obj = new $class();
                }
                if ($container instanceof MutableContainerInterface) {
                    try {
                        $container->set($class, $obj);
                    } catch (ContainerExceptionInterface $ignore) {
                    }
                }
            }
            if ($method) {
                return $invoker->call([$obj, $method], $params);
            }
            return is_callable($obj) ? $invoker->call($obj, $params) : $obj;
        }
        return $invoker->call($callable, $params);
    }
}
