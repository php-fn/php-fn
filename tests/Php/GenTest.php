<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Php\test\assert;
use PHPUnit\Framework\TestCase;

class GenTest extends TestCase
{
    public function testIterator(): void
    {
        $empty = static function () {
            '' && yield;
        };

        assert\same([], $this::gen());
        assert\same([], $this::gen([]));
        assert\same([], $this::gen($empty));
        assert\same([], $this::gen($empty, [], []));
        assert\same(['a' => 'A', 'b' => 'B'], $this::gen(['a' => 'a', 'b' => 'b'], static function ($v) {
            yield strtoupper($v);
        }));
        assert\same(['A', 'B'], $this::gen(['a' => 'a', 'b' => 'b'], static function ($v) {
            yield null => strtoupper($v);
        }));
        assert\same(array_merge(['a'], ['b']), $this::gen(['a'], ['b']));
        assert\same(array_merge(['a'], [1 => 'b', 'c' => 'c']), $this::gen(['a'], [1 => 'b', 'c' => 'c']));
        assert\same(
            array_merge(['a' => 'a'], ['b' => 'b', 'c' => 'c']),
            $this::gen($empty, ['a' => 'a'], ['b' => 'b', 'c' => 'c'])
        );
        assert\same(
            array_merge(['a' => 'a'], ['b' => 'b', 'c' => 'c']),
            $this::gen($empty, ['a' => 'a'], ['b' => 'b', 'c' => 'c'], static function ($value) {
                yield $value;
            })
        );
        assert\same(
            ['a' => 'a', 'b', 'c' => 'c'],
            $this::gen(['a' => 'a', 'b' => 'b', 'c' => 'c'], static function ($value) {
                $value === 'b' ? yield null => $value : yield $value;
            })
        );
        assert\same(
            ['A' => 'aa', 'B' => 'bb', 'C' => 'cc'],
            $this::gen(['a', 'b', 'c'], static function ($value) {
                yield [strtoupper($value)] => $value . $value;
            })
        );
        assert\same(
            ['a' => 'a', 'A' => 'a', 'b' => 'b', 'B' => 'b', 'c' => 'c', 'C' => 'c'],
            $this::gen(['a', 'b', 'c'], static function ($value) {
                yield [$value] => $value;
                yield [strtoupper($value)] => $value;
            })
        );
        assert\same(
            ['foo' => ['a' => 'A', 'b' => 'B'], 'bar' => ['c' => 'C']],
            $this::gen(['a', 'b', 'c'], static function ($value) {
                yield [$value === 'c' ? 'bar' : 'foo', $value] => strtoupper($value);
            })
        );
    }

    private static function gen(...$args): array
    {
        assert\same(iterator_to_array(new Gen(...$args)), $arr = Php::arr(...$args));
        return $arr;
    }
}
