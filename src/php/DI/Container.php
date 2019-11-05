<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\DI;

use DI\Definition\Definition;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Definition\Source\ReflectionBasedAutowiring;
use DI\Definition\Source\SourceChain;
use DI\Proxy\ProxyFactory;
use Psr\Container\ContainerInterface;

class Container extends \DI\Container implements MutableDefinitionSource
{
    use DefinitionSourceProxyTrait;

    /**
     * @inheritdoc
     */
    public function __construct(
        MutableDefinitionSource $definitionSource = null,
        ProxyFactory $proxyFactory = null,
        ContainerInterface $wrapperContainer = null
    ) {
        if (!$definitionSource) {
            /** @noinspection PhpUnhandledExceptionInspection */
            ($definitionSource = new SourceChain([new ReflectionBasedAutowiring]))->setMutableDefinitionSource(
                new DefinitionArray([], new ReflectionBasedAutowiring)
            );
        }
        parent::__construct($this->definitionSource = $definitionSource, $proxyFactory, $wrapperContainer);
        $this->resolvedEntries[self::class]   = $this;
        $this->resolvedEntries[static::class] = $this;
    }

    /**
     * @inheritdoc
     */
    public function addDefinition(Definition $definition)
    {
        $this->setDefinition($definition->getName(), $definition);
    }
}
