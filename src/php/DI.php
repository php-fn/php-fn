<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php;

use DI\CompiledContainer;
use DI\ContainerBuilder;
use DI\Definition\Source\DefinitionSource;
use php\DI\ContainerConfiguration;
use Psr\Container\ContainerInterface;

abstract class DI
{
    public const WIRING = 'wiring';
    public const CACHE = 'cache';
    public const PROXY = 'proxy';
    public const COMPILE = 'compile';

    /**
     * Create a container from the given definitions.
     * If the last parameter is a callable it will be invoked to get the container configuration.
     * If the last parameter is TRUE the container will be auto(by reflections) wired.
     *
     * @param string|array|DefinitionSource|callable|true ...$args
     * @return DI\Container|CompiledContainer
     */
    public static function create(...$args): ContainerInterface
    {
        $last = array_pop($args);

        if (isCallable($last)) {
            $config = $last();
            $config = is_array($config) ? $config : [self::WIRING => $config];
        } else if ($last === DI\WIRING::AUTO) {
            $config = [self::WIRING => DI\WIRING::AUTO];
        } else {
            $last && $args[] = $last;
            $config = [];
        }
        return static::config($config, ...$args)->container();
    }

    /**
     * @param array $config
     * @param string|array|DefinitionSource ...$definitions
     *
     * @return ContainerConfiguration
     */
    public static function config(array $config, ...$definitions): ContainerConfiguration
    {
        $builder = new ContainerBuilder(ContainerConfiguration::class);

        $builder->useAutowiring(false)->useAnnotations(false)->ignorePhpDocErrors(false);

        $wiring = $config[static::WIRING] ?? null;
        if (in_array($wiring, [DI\WIRING::REFLECTION, DI\WIRING::AUTO], true)) {
            $builder->useAutowiring(true);
        } else if ($wiring === DI\WIRING::STRICT) {
            $builder->useAnnotations(true)->ignorePhpDocErrors(false);
        } else if ($wiring === DI\WIRING::TOLERANT) {
            $builder->useAnnotations(true)->ignorePhpDocErrors(true);
        }

        empty($config[static::CACHE]) || $builder->enableDefinitionCache();
        empty($config[static::PROXY]) || $builder->writeProxiesToFile(true, $config[static::PROXY]);
        empty($config[static::COMPILE]) || $builder->enableCompilation($config[static::COMPILE]);

        foreach ($definitions as $definition) {
            $builder->addDefinitions($definition);
        }

        $built = $builder->build();
        return $built instanceof ContainerConfiguration ? $built : new ContainerConfiguration($built);
    }
}