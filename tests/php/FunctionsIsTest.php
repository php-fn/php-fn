<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Php\test\assert;
use PHPUnit\Framework\TestCase;

/**
 */
class FunctionsIsTest extends TestCase
{
    /**
     * @dataProvider providerIsCallable
     *
     * @param bool $expected
     * @param array $args
     */
    public function testIsCallable($expected, ...$args): void
    {
        assert\same($expected, Php::isCallable(...$args));
    }

    /**
     * @return array
     */
    public function providerIsCallable(): array
    {
        return [
            'closure' => [true, static function () {}],
            'closure tolerant' => [true, static function () {}, false],
            'closure strict' => [true, static function () {}, true],
            '__invoke' => [true, $this],
            '__invoke tolerant' => [true, $this, false],
            '__invoke strict' => [true, $this, true],
            '[c, m]' => [false, ['c', 'm']],
            '[c, m] tolerant' => [true, ['c', 'm'], false],
            '[c, m] strict' => [false, ['c', 'm'], true],
            '[c, m, third]' => [false, ['c', 'm', 'third']],
            '[c, m, third] tolerant' => [false, ['c', 'm', 'third'], false],
            '[c, m, third] strict' => [false, ['c', 'm', 'third'], true],
            '[$this, string]' => [false, [$this, 'string']],
            '[$this, string] tolerant' => [true, [$this, 'string'], false],
            '[$this, string] strict' => [false, [$this, 'string'], true],
            '[static, public]' => [true, [__CLASS__, 'staticPublic']],
            '[static, public] tolerant' => [true, [__CLASS__, 'staticPublic'], false],
            '[static, public] strict' => [true, [__CLASS__, 'staticPublic'], true],
            'count' => [false, 'count'],
            'count tolerant' => [true, 'count', false],
            'count strict' => [false, 'count', true],
        ];
    }

    /**
     * ignore
     */
    public static function staticPublic(): void
    {
    }

    /**
     * ignore
     */
    public function __invoke(): void
    {
    }
}
