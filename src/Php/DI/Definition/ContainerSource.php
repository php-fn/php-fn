<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI\Definition;

use DI\Definition\Source\DefinitionSource;
use Psr\Container\ContainerInterface;

class ContainerSource implements DefinitionSource
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getDefinition(string $name)
    {
        return $this->container->has($name) ? new ContainerEntryReference($name, $this->container) : null;
    }

    public function getDefinitions(): array
    {
        return [];
    }
}
