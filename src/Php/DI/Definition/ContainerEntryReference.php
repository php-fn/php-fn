<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI\Definition;

use DI\Definition\Reference;
use Psr\Container\ContainerInterface;

class ContainerEntryReference extends Reference
{
    private $container;

    public function __construct(string $targetEntryName, ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct($targetEntryName);
    }

    public function resolve(ContainerInterface $container)
    {
//        return ($container->has($this->getTargetEntryName()) ? $container : $this->container)->get($this->getTargetEntryName());
        return $this->container->get($this->getTargetEntryName());
    }

    public function isResolvable(ContainerInterface $container) : bool
    {
//        return $container->has($this->getTargetEntryName()) || $this->container->has($this->getTargetEntryName());
        return $this->container->has($this->getTargetEntryName());
    }
}
