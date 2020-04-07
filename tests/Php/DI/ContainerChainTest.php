<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use Php;
use DI;
use Php\Test\AssertTrait;
use PHPUnit\Framework\TestCase;

class ContainerChainTest extends TestCase
{
    use AssertTrait;

    public function testEmpty(): void
    {
        $chain = new ContainerChain();
        self::assertFalse($chain->has('id'));
        self::assertException(new DI\NotFoundException('id'), function () use ($chain) {
            $chain->get('id');
        });
    }

    public function testSingle(): void
    {
        $chain = new ContainerChain(Php\Php::di(['id' => 'value']));
        self::assertTrue($chain->has('id'));
        self::assertSame('value', $chain->get('id'));
        self::assertFalse($chain->has('foo'));
    }

    public function testChain(): void
    {
        $chain = new ContainerChain(
            Php\Php::di(['foo' => 'FOO', 'a' => 'A']),
            Php\Php::di(['foo' => 'BAR', 'b' => 'B'])
        );
        self::assertSame('FOO', $chain->get('foo'));
        self::assertSame('A', $chain->get('a'));
        self::assertSame('B', $chain->get('b'));

        $chain = new ContainerChain(Php\Php::di(['foo' => 'BAZ']), $chain);
        self::assertSame('BAZ', $chain->get('foo'));
        self::assertSame('A', $chain->get('a'));
        self::assertSame('B', $chain->get('b'));
    }
}
