<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI\Definition\ValueDefinition;
use Invoker\ParameterResolver\DefaultValueResolver;
use Php;
use Php\Test\AssertTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

class InvokerTest extends TestCase
{
    use AssertTrait;

    public function testResolve(): void
    {
        self::assertException('argument $candidate is not callable', static function () {
            (new Invoker)->resolve('count');
        });

        self::assertSame($func = static function () {}, (new Invoker)->resolve($func));
        self::assertSame([$this, __FUNCTION__], (new Invoker)->resolve([$this, __FUNCTION__]));

        $resolver = new Invoker(Php\Php::di(['callback' => new ValueDefinition($func)]));
        self::assertSame($func, $resolver->resolve('callback'));
    }

    public function testReflect(): void
    {
        $resolver = $this->resolver(['callback' => new ValueDefinition(static function (string $s1) {})]);
        self::assertInstanceOf(ReflectionFunction::class, $resolver->reflect('callback'));
        self::assertInstanceOf(ReflectionMethod::class, $resolver->reflect([$this, __FUNCTION__]));
    }

    public function testParameters(): void
    {
        $resolver = $this->resolver([TestCase::class => $this, static::class => $this]);
        $callback = static function (
            string $p1,
            self $p2,
            bool $p3 = false,
            TestCase $p4 = null
        ) {};

        self::assertEquals([1 => $this, 3 => $this, 2 => false], $resolver->parameters($callback));
        self::assertEquals(
            [1 => $this, 3 => $this, 2 => true, 0 => false],
            $resolver->parameters($callback, ['P3' => true, 'p1' => false])
        );
    }

    public function testCall(): void
    {
        $resolver = $this->resolver([self::class => $this]);
        $callable = static function (self $test, $default = true, $provided = null) {
            return [$test, $default, $provided];
        };
        $actual = $resolver->call($callable, ['provided' => 'value']);
        self::assertSame([$this, true, 'value'], $actual);
    }

    public function testTaggedParameter(): void
    {
        $invoker = new Invoker(static function (ReflectionParameter $par) {
            yield [$par->getName(), $par->description, (string)$par->types];
        });

        self::assertSame([
            ['string', 'foo', 'string'],
            ['bool', 'bar', 'bool'],
            ['strings', '', 'string[]'],
        ], $invoker->parameters(
            /**
             * @param string $string foo
             * @param bool $bool bar
             * @param string[] $strings
             */
            static function(string $string, bool $bool, array $strings) {
            }
        ));
    }

    private function resolver(array $definition = []): Invoker
    {
        return new Invoker(
            Php\Php::di($definition),
            static function (\ReflectionParameter $parameter, array $provided) {
                $map = Php::map(array_change_key_case($provided));
                if (($value = $map[strtolower($parameter->getName())] ?? null) !== null) {
                    yield $value;
                }
            },
            new DefaultValueResolver()
        );
    }
}
