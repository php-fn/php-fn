<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI\Container as DIContainer;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Proxy\ProxyFactory;
use Psr\Container\ContainerInterface;

class Container extends DIContainer
{
    public function __construct(
        MutableDefinitionSource $definitions = null,
        ProxyFactory $proxyFactory = null,
        ContainerInterface $wrapper = null
    ) {
        parent::__construct($definitions, $proxyFactory, $wrapper ? new ContainerChain($this, $wrapper) : null);
        $this->resolvedEntries[self::class]   = $this;
        $this->resolvedEntries[static::class] = $this;
    }
}
