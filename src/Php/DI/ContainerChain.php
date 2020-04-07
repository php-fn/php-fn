<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI\NotFoundException;
use Psr\Container\ContainerInterface;

class ContainerChain implements ContainerInterface
{
    private $containers;

    public function __construct(ContainerInterface ...$containers)
    {
        $this->containers = $containers;
    }

    protected function findContainer($id): ?ContainerInterface
    {
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container;
            }
        }
        return null;
    }

    public function get($id)
    {
        if ($container = $this->findContainer($id)) {
            return $container->get($id);
        }
        throw new NotFoundException($id);
    }

    public function has($id): bool
    {
        return (bool) $this->findContainer($id);
    }
}
