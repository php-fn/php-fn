<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use Psr\Container\ContainerInterface;
use Php;

trait AwareTrait
{
    /**
     * @param string|iterable|null $name
     * @param mixed $default
     *
     * @return mixed|ContainerInterface
     */
    public function di($name = null, $default = null)
    {
        if (($container = $this->container ?? $this) && !$container instanceof ContainerInterface) {
            Php::fail('$container property or $this is not an instance of %s', ContainerInterface::class);
        }
        if (is_iterable($name)) {
            return Php::arr($name, static function ($default, $name) use ($container) {
                if (is_numeric($name)) {
                    $name = $default;
                    $default = null;
                }
                yield [$name] => $container->has($name) ? $container->get($name) : $default;
            });
        }
        if ($name === null) {
            return $container;
        }
        return $container->has($name) ? $container->get($name) : $default;
    }
}
