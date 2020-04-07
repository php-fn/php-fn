<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use ArrayAccess;
use Php;
use ArrayIterator;
use DI\Annotation\Inject;
use DI\CompiledContainer;
use DI\Definition\Source\SourceChain;
use DI\Proxy\ProxyFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class ContainerConfigurationTest extends TestCase
{
    /**
     * @Inject("foo")
     */
    private $foo;

    public function testConfig(): void
    {
        self::assertInstanceOf(ContainerConfiguration::class, $config = ContainerConfiguration::config([], ['foo' => 'bar']));
        self::assertEquals(new ProxyFactory, $config->getProxyFactory());
        self::assertInstanceOf(SourceChain::class, $config->getDefinitionSource());
        self::assertInstanceOf(Container::class, $container = $config->container());
        self::assertSame('bar', $container->get('foo'));

        self::assertFalse($container->has(__CLASS__));

        self::assertInstanceOf(ContainerConfiguration::class, $config = ContainerConfiguration::config([ContainerConfiguration::PROXY => __DIR__], ['foo' => 'bar']));
        self::assertEquals(new ProxyFactory(true, __DIR__), $config->getProxyFactory());
        self::assertInstanceOf(SourceChain::class, $config->getDefinitionSource());
        self::assertInstanceOf(Container::class, $container = $config->container());
        self::assertSame('bar', $container->get('foo'));
        self::assertFalse($container->has(__CLASS__));

        self::assertInstanceOf(ContainerConfiguration::class, $config = ContainerConfiguration::config(
            [ContainerConfiguration::COMPILE => sys_get_temp_dir()],
            ['foo' => 'bar']
        ));
        self::assertNull($config->getProxyFactory());
        self::assertInstanceOf(CompiledContainer::class, $container = $config->container());
        self::assertSame($container, $config->getDefinitionSource());
        self::assertSame('bar', $container->get('foo'));
        self::assertFalse($container->has(__CLASS__));

        $this->assertWiring(null, Wiring::AUTO);
        $this->assertWiring(null, Wiring::REFLECTION);
        $this->assertWiring('bar', Wiring::STRICT);
        $this->assertWiring('bar', Wiring::TOLERANT);
    }

    public function testCreate(): void
    {
        self::assertInstanceOf(Container::class, Php\Php::di());
        self::assertFalse(Php\Php::di()->has('foo'));
        self::assertSame('bar', Php\Php::di(['foo' => 'bar'])->get('foo'));
        self::assertFalse(Php\Php::di()->has(__CLASS__));
        self::assertInstanceOf(__CLASS__, Php\Php::di(Wiring::AUTO)->get(__CLASS__));
        self::assertSame('bar', Php\Php::di(['foo' => 'bar'], function () {
            return Wiring::STRICT;
        })->get(__CLASS__)->foo);

        $di = Php\Php::di(['foo' => 'bar'], ['bar' => 'foo'], function () {
            return [
                ContainerConfiguration::WIRING => Wiring::NONE,
                ContainerConfiguration::COMPILE => sys_get_temp_dir(),
                ContainerConfiguration::PROXY => sys_get_temp_dir(),
            ];
        });
        self::assertFalse($di->has(__CLASS__));
        self::assertSame('bar', $di->get('foo'));
        self::assertSame('foo', $di->get('bar'));
    }


    public function testInner(): void
    {
        $inner = new Container();
        $inner->set('innerValue', true);
        $inner->set(ArrayAccess::class, function () {
            return new class implements ArrayAccess {
                use Php\ArrayAccessTrait;
            };
        });

        $outer = Php\Php::di([
            'useInnerClass' => function (ArrayAccess $obj) {
                return $obj;
            },
            'useLateDefinition' => function (stdClass $obj) {
                return $obj;
            },
            'useLateValue' => function (TestCase $obj) {
                return $obj;
            },
        ], $inner);
        self::assertTrue($outer->has('innerValue'));
        self::assertTrue($outer->has(ArrayAccess::class));

        $outer->set(stdClass::class, function () {
            return (object)[true];
        });
        self::assertTrue($outer->has('useLateDefinition'));

        $outer->set(TestCase::class, $this);
        self::assertTrue($outer->has('useLateValue'));
    }

    public function testWrappedContainers(): void
    {
        $foreign = new class implements ContainerInterface {
            private $value;
            public function get($id)
            {
                $this->has($id) || Php::fail($id);
                return $this->value ?? $this->value = new ArrayIterator([]);
            }

            public function has($id): bool
            {
                return $id === ArrayIterator::class;
            }
        };

        $stdClassToArray = function (stdClass $obj): array {
            return (array)$obj;
        };

        $outer = Php\Php::di(
            // inner 1
            $inner1 = Php\Php::di([
                stdClass::class => (object)['inner-1'],
                'shared' => 'inner-1',
                'inner-1' => $stdClassToArray,
            ]),
            // outer
            [
                stdClass::class => (object)['outer'],
                'shared' => 'outer',
                'outer' => $stdClassToArray,
                'foreign' => function (ArrayIterator $it, stdClass $obj) {
                    return $it && $obj;
                }
            ],
            // inner 2
            $inner2 = Php\Php::di([
                stdClass::class => (object)['inner-2'],
                'shared' => 'inner-2',
                'inner-2' => $stdClassToArray,
            ]),
            $foreign
        );
        self::assertSame(['inner-1'], $outer->get('inner-1'));
        self::assertSame(['inner-2'], $outer->get('inner-2'));
        self::assertSame('inner-2', $outer->get('shared'));

        self::assertSame('inner-1', $inner1->get('shared'));
        self::assertSame(['inner-1'], $inner1->get('inner-1'));
        self::assertFalse($inner1->has('inner-2'));

        self::assertSame('inner-2', $inner2->get('shared'));
        self::assertSame(['inner-2'], $inner2->get('inner-2'));
        self::assertFalse($inner2->has('inner-1'));

        self::assertTrue($outer->get('foreign'));
    }

    private function assertWiring($expectedFoo, $wiring): void
    {
        self::assertInstanceOf(ContainerConfiguration::class, $config = ContainerConfiguration::config(
            [ContainerConfiguration::WIRING => $wiring],
            ['foo' => 'bar'])
        );
        self::assertEquals(new ProxyFactory(), $config->getProxyFactory());
        self::assertInstanceOf(SourceChain::class, $config->getDefinitionSource());
        self::assertInstanceOf(Container::class, $container = $config->container());
        self::assertSame('bar', $container->get('foo'));
        self::assertTrue($container->has(__CLASS__));
        self::assertEquals($expectedFoo, $container->get(__CLASS__)->foo);
    }
}
