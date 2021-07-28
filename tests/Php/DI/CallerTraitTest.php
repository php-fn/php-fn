<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use Php;
use Php\Test\AssertTrait;
use PHPUnit\Framework\TestCase;

class CallerTraitTest extends TestCase
{
    use AssertTrait;

    public function testCall(): void
    {
        self::assertException('$container property is not an instance of', function () {
            $caller = new class {
                use CallerTrait;
            };
            $caller->call(null);
        });

        self::assertException('invoker is not an instance of', function () {
            $caller = new class {
                public $container;
                use CallerTrait;
            };
            $caller->container = Php\Php::di();
            $caller->call(null);
        });

        $caller = new class {
            public $container;
            use CallerTrait;
        };
        $caller->container = Php\Php::di();
        $invoker = new Invoker(
            $caller->container, // last parameter $invoker is resolved first
            Php\Php::di([self::class => $this]), // first parameter $test is resolved last
        );
        $caller->container->set(Invoker::class, $invoker);
        self::assertSame($invoker, $caller->call(Invoker::class));
        self::assertTrue($caller->call(function (Invoker $invoker) {
            return $invoker instanceof Invoker;
        }));

        self::assertInstanceOf(
            Bar::class,
            $caller->call(Bar::class),
            'parameters are sorted by @see Invoker::parameters()'
        );
    }
}

abstract class Foo
{
    public function __construct(CallerTraitTest $test, Invoker $invoker)
    {
    }
}

class Bar extends Foo
{

}
