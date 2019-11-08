<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use DI\Annotation\Inject;
use DI\CompiledContainer;
use DI\Definition\Source\SourceChain;
use DI\Proxy\ProxyFactory;
use Php\test\assert;
use PHPUnit\Framework\TestCase;

class DITest extends TestCase
{
    /**
     * @Inject("foo")
     */
    private $foo;

    public function testConfig(): void
    {
        assert\type(DI\ContainerConfiguration::class, $config = DI::config([], ['foo' => 'bar']));
        assert\equals(new ProxyFactory, $config->getProxyFactory());
        assert\type(SourceChain::class, $config->getDefinitionSource());
        assert\type(DI\Container::class, $container = $config->container());
        assert\same('bar', $container->get('foo'));
        assert\false($container->has(__CLASS__));

        assert\type(DI\ContainerConfiguration::class, $config = DI::config([DI::PROXY => __DIR__], ['foo' => 'bar']));
        assert\equals(new ProxyFactory(true, __DIR__), $config->getProxyFactory());
        assert\type(SourceChain::class, $config->getDefinitionSource());
        assert\type(DI\Container::class, $container = $config->container());
        assert\same('bar', $container->get('foo'));
        assert\false($container->has(__CLASS__));

        assert\type(DI\ContainerConfiguration::class, $config = DI::config(
            [DI::COMPILE => sys_get_temp_dir()],
            ['foo' => 'bar']
        ));
        assert\same(null, $config->getProxyFactory());
        assert\type(CompiledContainer::class, $container = $config->container());
        assert\same($container, $config->getDefinitionSource());
        assert\same('bar', $container->get('foo'));
        assert\false($container->has(__CLASS__));

        $this->assertWiring(null, DI\WIRING::AUTO);
        $this->assertWiring(null, DI\WIRING::REFLECTION);
        $this->assertWiring('bar', DI\WIRING::STRICT);
        $this->assertWiring('bar', DI\WIRING::TOLERANT);
    }

    public function testCreate(): void
    {
        assert\type(DI\Container::class, DI::create());
        assert\false(DI::create()->has('foo'));
        assert\same('bar', DI::create(['foo' => 'bar'])->get('foo'));
        assert\false(DI::create()->has(__CLASS__));
        assert\type(__CLASS__, DI::create(DI\WIRING::AUTO)->get(__CLASS__));
        assert\same('bar', DI::create(['foo' => 'bar'], function () {
            return DI\WIRING::STRICT;
        })->get(__CLASS__)->foo);

        $di = DI::create(['foo' => 'bar'], ['bar' => 'foo'], function () {
            return [
                DI::WIRING => DI\WIRING::NONE,
                DI::COMPILE => sys_get_temp_dir(),
                DI::PROXY => sys_get_temp_dir(),
            ];
        });
        assert\false($di->has(__CLASS__));
        assert\same('bar', $di->get('foo'));
        assert\same('foo', $di->get('bar'));
    }

    private function assertWiring($expectedFoo, $wiring): void
    {
        assert\type(DI\ContainerConfiguration::class, $config = DI::config(
            [DI::WIRING => $wiring],
            ['foo' => 'bar'])
        );
        assert\equals(new ProxyFactory, $config->getProxyFactory());
        assert\type(SourceChain::class, $config->getDefinitionSource());
        assert\type(DI\Container::class, $container = $config->container());
        assert\same('bar', $container->get('foo'));
        assert\true($container->has(__CLASS__));
        assert\equals($expectedFoo, $container->get(__CLASS__)->foo);
    }
}
