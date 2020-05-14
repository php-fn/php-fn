<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI\Definition\Helper\DefinitionHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

interface MutableContainerInterface extends ContainerInterface
{
    /**
     * Define an object or a value in the container.
     *
     * @param string $id Entry name
     * @param mixed|DefinitionHelper $value Value, use definition helpers to define objects
     * @throws ContainerExceptionInterface
     */
    public function set(string $id, $value);
}
