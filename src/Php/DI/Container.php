<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI;
use Psr\Container\ContainerInterface;

class Container extends DI\Container implements MutableContainerInterface
{
    public function __construct(
        DI\Definition\Source\MutableDefinitionSource $definitions = null,
        DI\Proxy\ProxyFactory $proxyFactory = null,
        ContainerInterface $wrapper = null
    ) {
        parent::__construct($definitions, $proxyFactory, $wrapper ? new ContainerChain($this, $wrapper) : null);
        $this->resolvedEntries[self::class]   = $this;
        $this->resolvedEntries[static::class] = $this;
    }
}
