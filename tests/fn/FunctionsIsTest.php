<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\test\assert;

/**
 */
class FunctionsIsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::isCallable
     * @dataProvider providerIsCallable
     * @param bool $expected
     * @param array $args
     */
    public function testIsCallable($expected, ...$args): void
    {
        assert\same($expected, isCallable(...$args));
    }

    /**
     * @return array
     */
    public function providerIsCallable(): array
    {
        return [
            'closure' => [true, function () {}],
            'closure tolerant' => [true, function () {}, false],
            'closure strict' => [true, function () {}, true],
            '__invoke' => [true, $this],
            '__invoke tolerant' => [true, $this, false],
            '__invoke strict' => [true, $this, true],
            '[c, m]' => [true, ['c', 'm']],
            '[c, m] tolerant' => [true, ['c', 'm'], false],
            '[c, m] strict' => [false, ['c', 'm'], true],
            '[c, m, third]' => [false, ['c', 'm', 'third']],
            '[c, m, third] tolerant' => [false, ['c', 'm', 'third'], false],
            '[c, m, third] strict' => [false, ['c', 'm', 'third'], true],
            '[$this, string]' => [true, [$this, 'string']],
            '[$this, string] tolerant' => [true, [$this, 'string'], false],
            '[$this, string] strict' => [false, [$this, 'string'], true],
            '[static, public]' => [true, [__CLASS__, 'staticPublic']],
            '[static, public] tolerant' => [true, [__CLASS__, 'staticPublic'], false],
            '[static, public] strict' => [true, [__CLASS__, 'staticPublic'], true],
            'count' => [true, 'count'],
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
