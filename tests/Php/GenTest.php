<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use PHPUnit\Framework\TestCase;

class GenTest extends TestCase
{
    public function testIterator(): void
    {
        $empty = static function () {
            '' && yield;
        };

        $this::assertSame([], $this::gen());
        $this::assertSame([], $this::gen([]));
        $this::assertSame([], $this::gen($empty));
        $this::assertSame([], $this::gen($empty, [], []));
        $this::assertSame(['a' => 'A', 'b' => 'B'], $this::gen(['a' => 'a', 'b' => 'b'], static function ($v) {
            yield strtoupper($v);
        }));
        $this::assertSame(['A', 'B'], $this::gen(['a' => 'a', 'b' => 'b'], static function ($v) {
            yield null => strtoupper($v);
        }));
        $this::assertSame(array_merge(['a'], ['b']), $this::gen(['a'], ['b']));
        $this::assertSame(array_merge(['a'], [1 => 'b', 'c' => 'c']), $this::gen(['a'], [1 => 'b', 'c' => 'c']));
        $this::assertSame(
            array_merge(['a' => 'a'], ['b' => 'b', 'c' => 'c']),
            $this::gen($empty, ['a' => 'a'], ['b' => 'b', 'c' => 'c'])
        );
        $this::assertSame(
            array_merge(['a' => 'a'], ['b' => 'b', 'c' => 'c']),
            $this::gen($empty, ['a' => 'a'], ['b' => 'b', 'c' => 'c'], static function ($value) {
                yield $value;
            })
        );
        $this::assertSame(
            ['a' => 'a', 'b', 'c' => 'c'],
            $this::gen(['a' => 'a', 'b' => 'b', 'c' => 'c'], static function ($value) {
                $value === 'b' ? yield null => $value : yield $value;
            })
        );
        $this::assertSame(
            ['A' => 'aa', 'B' => 'bb', 'C' => 'cc'],
            $this::gen(['a', 'b', 'c'], static function ($value) {
                yield [strtoupper($value)] => $value . $value;
            })
        );
        $this::assertSame(
            ['a' => 'a', 'A' => 'a', 'b' => 'b', 'B' => 'b', 'c' => 'c', 'C' => 'c'],
            $this::gen(['a', 'b', 'c'], static function ($value) {
                yield [$value] => $value;
                yield [strtoupper($value)] => $value;
            })
        );
        $this::assertSame(
            ['foo' => ['a' => 'A', 'b' => 'B'], 'bar' => ['c' => 'C']],
            $this::gen(['a', 'b', 'c'], static function ($value) {
                yield [$value === 'c' ? 'bar' : 'foo', $value] => strtoupper($value);
            })
        );

        $this::assertSame(
            ['a' => 'A', 'b' => 'B'],
            $this::gen(static function () {
                yield ['a'] => 'A';
                yield ['b'] => 'B';
            })
        );

        $this::assertSame(
            ['a' => ['a' => 'aa', 'b' => 'ab'], 'b' => ['a' => 'ba']],
            $this::gen(static function () {
                yield ['a', 'a'] => 'aa';
                yield ['a', 'b'] => 'ab';
                yield ['b', 'a'] => 'ba';
            })
        );
    }

    private static function gen(...$args): array
    {
        self::assertSame(iterator_to_array(new Gen(...$args)), $arr = Php::arr(...$args));
        return $arr;
    }
}
