<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use Php;
use DI\ContainerBuilder;
use DI\Definition\Source\DefinitionSource;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Proxy\ProxyFactory;
use Php\DI\Definition\ContainerSource;
use Psr\Container\ContainerInterface;

class ContainerConfiguration
{
    public const WIRING = 'wiring';
    public const CACHE = 'cache';
    public const PROXY = 'proxy';
    public const COMPILE = 'compile';

    /**
     * @var MutableDefinitionSource|ContainerInterface
     */
    private $definitionSource;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var ContainerInterface
     */
    private $wrapperContainer;

    /**
     * @param MutableDefinitionSource|ContainerInterface $definitionSource
     * @param ProxyFactory                               $proxyFactory
     * @param ContainerInterface                         $wrapperContainer
     */
    public function __construct(
        $definitionSource = null,
        ProxyFactory $proxyFactory = null,
        ContainerInterface $wrapperContainer = null
    ) {
        $this->definitionSource = $definitionSource;
        $this->proxyFactory     = $proxyFactory;
        $this->wrapperContainer = $wrapperContainer;
    }

    /**
     * @return MutableDefinitionSource|ContainerInterface
     */
    public function getDefinitionSource()
    {
        return $this->definitionSource;
    }

    /**
     * @return ProxyFactory
     */
    public function getProxyFactory(): ?ProxyFactory
    {
        return $this->proxyFactory;
    }

    /**
     * @return ContainerInterface
     */
    public function getWrapperContainer(): ?ContainerInterface
    {
        return $this->wrapperContainer;
    }

    public function container(string $class = Container::class): ContainerInterface
    {
        if (($source = $this->getDefinitionSource()) instanceof MutableDefinitionSource) {
            return new $class($source, $this->getProxyFactory(), $this->getWrapperContainer());
        }
        return $source;
    }

    public static function create(...$args): self
    {
        $last = array_pop($args);

        if (Php::isCallable($last)) {
            $config = $last();
            $config = is_array($config) ? $config : [self::WIRING => $config];
        } else if ($last === Wiring::AUTO) {
            $config = [self::WIRING => Wiring::AUTO];
        } else {
            $last && $args[] = $last;
            $config = [];
        }
        return static::config($config, ...$args);
    }

    /**
     * @param array $config
     * @param string|array|DefinitionSource|ContainerInterface ...$args
     *
     * @return self
     */
    public static function config(array $config, ...$args): self
    {
        $builder = new ContainerBuilder(self::class);

        $builder->useAutowiring(false)->useAnnotations(false)->ignorePhpDocErrors(false);

        $wiring = $config[static::WIRING] ?? null;
        if (in_array($wiring, [Wiring::REFLECTION, Wiring::AUTO], true)) {
            $builder->useAutowiring(true);
        } else if ($wiring === Wiring::STRICT) {
            $builder->useAnnotations(true)->ignorePhpDocErrors(false);
        } else if ($wiring === Wiring::TOLERANT) {
            $builder->useAnnotations(true)->ignorePhpDocErrors(true);
        }

        empty($config[static::CACHE]) || $builder->enableDefinitionCache();
        empty($config[static::PROXY]) || $builder->writeProxiesToFile(true, $config[static::PROXY]);
        empty($config[static::COMPILE]) || $builder->enableCompilation($config[static::COMPILE]);

        $chain = [];
        foreach ($args as $arg) {
            if ($arg instanceof ContainerInterface) {
                $chain[] = $arg;
                $arg = new ContainerSource($arg);
            }
            if (is_string($arg) || is_array($arg) || $arg instanceof DefinitionSource) {
                $builder->addDefinitions($arg);
            }
        }
        if ($chain) {
            $builder->wrapContainer(count($chain) > 1 ? new ContainerChain(...$chain) : $chain[0]);
        }
        $built = $builder->build();
        return $built instanceof self ? $built : new self($built);
    }
}
