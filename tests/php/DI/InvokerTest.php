<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\DI;

use function DI\value;
use php;
use php\test\assert;
use Invoker\ParameterResolver\DefaultValueResolver;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @coversDefaultClass Invoker
 */
class InvokerTest extends TestCase
{
    /**
     * @covers \php\DI\Invoker::resolve
     */
    public function testResolve(): void
    {
        assert\exception('argument $candidate is not callable', static function () {
            (new Invoker)->resolve('count');
        });

        assert\same($func = static function () {}, (new Invoker)->resolve($func));
        assert\same([$this, __FUNCTION__], (new Invoker)->resolve([$this, __FUNCTION__]));

        $resolver = new Invoker(php\DI::create(['callback' => value($func)]));
        assert\same($func, $resolver->resolve('callback'));
    }

    /**
     * @covers \php\DI\Invoker::reflect
     */
    public function testReflect(): void
    {
        $resolver = $this->resolver(['callback' => value(static function (string $s1) {})]);
        assert\type(ReflectionFunction::class, $resolver->reflect('callback'));
        assert\type(ReflectionMethod::class, $resolver->reflect([$this, __FUNCTION__]));
    }

    /**
     * @covers \php\DI\Invoker::parameters
     */
    public function testParameters(): void
    {
        $resolver = $this->resolver([TestCase::class => $this, static::class => $this]);
        $callback = static function (
            string $p1,
            self $p2,
            bool $p3 = false,
            TestCase $p4 = null
        ) {};

        assert\equals([1 => $this, 3 => $this, 2 => false], $resolver->parameters($callback));
        assert\equals(
            [1 => $this, 3 => $this, 2 => true, 0 => false],
            $resolver->parameters($callback, ['P3' => true, 'p1' => false])
        );
    }

    /**
     * @covers \php\DI\Invoker::call
     */
    public function testCall(): void
    {
        assert\same(
            [$this, true, 'value'],
            $this->resolver([static::class => $this])->call(
                static function (self $test, $default = true, $provided = null) {
                    return [$test, $default, $provided];
                },
                ['provided' => 'value']
            )
        );
    }

    /**
     * @covers \php\DI\ReflectionParameter::resolveDescription
     * @covers \php\DI\ReflectionParameter::resolveTypes
     */
    public function testTaggedParameter(): void
    {
        $invoker = new Invoker(static function (ReflectionParameter $par) {
            yield [$par->getName(), $par->description, (string)$par->types];
        });

        assert\same([
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
            php\DI::create($definition),
            static function (\ReflectionParameter $parameter, array $provided) {
                $map = php\map(array_change_key_case($provided));
                if (($value = $map[strtolower($parameter->getName())] ?? null) !== null) {
                    yield $value;
                }
            },
            new DefaultValueResolver
        );
    }
}
