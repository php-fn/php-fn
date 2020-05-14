<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Php;

class ContainerChain implements MutableContainerInterface
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

    public function set(string $id, $value): void
    {
        foreach ($this->containers as $container) {
            if ($container instanceof MutableContainerInterface) {
                $container->set($id, $value);
                return;
            }
        }
        throw new class(
            Php::str('missing %s', MutableContainerInterface::class)
        ) extends Exception implements ContainerExceptionInterface{};
    }
}
