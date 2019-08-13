<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php;

use ArrayObject;
use php\test\assert;

class FunctionsMapTest extends MapTest
{
    /**
     * @inheritdoc
     */
    protected function map(...$arguments): Map
    {
        return map(...$arguments);
    }

    public function testHasKey(): void
    {
        assert\false(hasKey('key', null));
        assert\false(hasKey('key', (object)[]));
        assert\false(hasKey('key', []));
        assert\false(hasKey('key', map()));

        assert\true(hasKey('key', map(['key' => null])));
        assert\true(hasKey('key', ['key' => null]));

        assert\true(hasKey('key', map(['key' => false])));
        assert\true(hasKey('key', ['key' => false]));

        assert\true(hasKey('key', map(['key' => 0])));
        assert\true(hasKey('key', ['key' => 0]));
        assert\true(hasKey('key', ['key' => 0]));

        assert\true(hasKey(0, 'a'));
        assert\false(hasKey(0, ''));
    }

    public function testHasValue(): void
    {
        assert\false(hasValue('value', null));
        assert\false(hasValue('value', []));
        assert\false(hasValue('value', map()));

        assert\true(hasValue(100, [100]));
        assert\false(hasValue('100', [100]));
        assert\true(hasValue('100', [100], false));

        assert\true(hasValue(100, map([100])));
        assert\false(hasValue('100', map([100])));
        assert\true(hasValue('100', map([100]), false));
    }

    public function testMapReplace(): void
    {
        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            traverse(map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A']
            )),
            'two iterable arguments => replace'
        );

        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'c', 'd'],
            traverse(map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                map(['c' => 'c', 'd'])
            )),
            'three iterable arguments => replace'
        );

        assert\same(
            ['k:a' => 'v:A', 'k:b' => 'v:b', 'k:c' => 'v:C'],
            traverse(map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                static function ($value, $key) {
                    return mapValue("v:$value")->andKey("k:$key");
                }
            )),
            'last argument is callable => replace and map'
        );
    }

    /**
     * @return array[]
     */
    public function providerSameBehaviourTraverseAndMap(): array
    {
        $toGroup = [['a', 'a'], ['a', 'b'], ['b', 'b'], ['b', 'a']];

        return [
            'mapValue(mapNull())' => [
                [null],
                ['v'],
                static function() {
                    return mapValue(mapNull());
                }
            ],

            'php\map should not merge single iterable' => [
                [2 => 'numeric-key'],
                [2 => 'numeric-key']
            ],
            'without Map\Value class' => [
                ['v1' => 'k1', 'v2' => 'k2'],
                ['k1' => 'v1', 'k2' => 'v2'],
                static function ($value, &$key) {
                    $tmp = $key;
                    $key = $value;
                    return $tmp;
                }
            ],
            'using mapBreak() and mapNull()' => [
                [1 => null, 3 => 'd'],
                ['a', 'b', 'c', 'd', 'e', 'f'],
                static function ($value) {
                    if ($value === 'e') {
                        return mapBreak();
                    }
                    if (in_array($value, ['a', 'c'], true)) {
                        return null;
                    }

                    return $value === 'b' ? mapNull() : $value;
                },
            ],
            'using mapValue() and mapKey()' => [
                ['VALUE', 'KEY' => 'key', 'pair' => 'flip', 'no' => 'changes'],
                ['value', 'key', 'flip' => 'pair', 'no' => 'changes'],
                static function ($value, $key) {
                    if ($value === 'value') {
                        return mapValue('VALUE');
                    }
                    if ($value === 'key') {
                        return mapKey('KEY');
                    }
                    if ($key === 'flip') {
                        return mapValue($key)->andKey($value);
                    }

                    return mapValue();
                },
            ],
            'group by a single value' => [
                [
                    'a' => [
                        0 => ['a', 'a'],
                        1 => ['a', 'b'],
                    ],
                    'b' => [
                        2 => ['b', 'b'],
                        3 => ['b', 'a'],
                    ],
                ],
                $toGroup,
                static function ($value) {
                    return mapGroup($value[0]);
                },
            ],
            'group by multiple values, with key' => [
                [
                    'a' => [
                        'a' => [100 => ['a', 'a']],
                        'b' => [101 => ['a', 'b']],
                    ],
                    'b' => [
                        'b' => [102 => ['b', 'b']],
                        'a' => [103 => ['b', 'a']],
                    ],
                ],
                $toGroup,
                static function($value, $key) {
                    return mapGroup($value)->andKey($key + 100);
                },
            ],
        ];
    }

    /**
     * @dataProvider providerSameBehaviourTraverseAndMap
     *
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourTraverse($expected, $iterable, ...$mapper): void
    {
        assert\same($expected, traverse($iterable, ...$mapper));
    }

    /**
     * @dataProvider providerSameBehaviourTraverseAndMap
     *
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourMap($expected, $iterable, ...$mapper): void
    {
        assert\same($expected, map($iterable, ...$mapper)->traverse);
    }

    public function testTraverse(): void
    {
        $emptyCallable = static function () {};

        assert\same(['key' => 'value'], traverse(['key' => 'value']));
        assert\same(['key' => 'value'], traverse(new ArrayObject(['key' => 'value'])));
        assert\same([], traverse(_\toArray(null, true)));
        assert\same([], traverse(_\toArray(null, true), $emptyCallable));

        assert\exception('argument $candidate must be traversable', static function () {
            traverse(null);
        });
        assert\exception('argument $traversable must be traversable', static function ($emptyCallable) {
            traverse(null, $emptyCallable);
        }, $emptyCallable);
        assert\same(['VALUE'], traverse(_\toArray('value', true), $this));

        if (PHP_VERSION_ID < 70200) {
            assert\same([1], traverse(_\toArray('value', true), 'count'));
        }
    }

    public function testNullBreak(): void
    {
        assert\equals((object)[], mapNull());
        assert\same(mapNull(), mapNull());

        assert\equals((object)[], mapBreak());
        assert\same(mapBreak(), mapBreak());

        assert\equals(mapBreak(), mapNull());
        assert\not\same(mapBreak(), mapNull());
    }

    public function testValueFunctions(): void
    {
        assert\equals(new Map\Value, mapValue());
        assert\equals(new Map\Value('v'), mapValue('v'));
        assert\equals(new Map\Value('v', 'k'), mapValue('v', 'k'));
        assert\equals(new Map\Value('v', 'k', 'g'), mapValue('v', 'k', 'g'));
        assert\equals(new Map\Value('v', 'k', 'g', 'c'), mapValue('v', 'k', 'g', 'c'));

        assert\equals((new Map\Value)->andKey('k'), mapKey('k'));
        assert\equals((new Map\Value)->andGroup('g'), mapGroup('g'));
        assert\equals((new Map\Value)->andChildren('c'), mapChildren('c'));
    }

    public function testMap(): void
    {
        assert\type(Map::class, map());
        assert\equals([], map()->traverse, 'args = 0');
        assert\equals(
            ['a', 'b', 'c', 'd', 'e'],
            map(['a', 'b'], ['c'], ['d', 'e'])->traverse,
            'args > 1, no mapper'
        );
        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            map(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e'])->traverse,
            'args > 1, no mapper, with assoc key'
        );
        assert\equals(['A', 'C', 'D', 'E'], map(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })->values, 'args > 1, with mapper');

        // array_key_exists('a', array_merge(['a' => 'A'], ['b' => 'B']))
        assert\true(isset(map(['a' => null], ['b' => 'B'])['a']));

        // in_array('B', array_merge(['a' => 'A'], ['b' => 'B']), true)
        assert\true(map(['a' => null], ['b' => 'B'])->has('B'));

        // array_merge(['a' => 'A'], ['b' => 'B'])['b']
        assert\same('B', map(['a' => 'A'], ['b' => 'B'])['b']);
    }

    public function testMerge(): void
    {
        assert\same([], merge(), 'args = 0');
        assert\same([], merge([]));
        assert\same([], merge([], new Map));
        assert\equals(['a', 'b', 'c', 'd', 'e'], merge(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            merge(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals(['A', 'C', 'D', 'E'], _\toValues(merge(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');

        assert\same('B', merge(['a' => 'A'], ['b' => 'B'])['b']);
    }

    public function testValues(): void
    {
        assert\same([], values(), 'args = 0');
        assert\same([], values([]));
        assert\same([], values([], new Map));
        assert\same(['a', 'b', 'c', 'd', 'e'], merge(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\same(
            ['a', 'b', 'c', 'd', 'e'],
            values(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\same(['A', 2 => 'C', 'D', 'E'], values(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        }), 'args > 1, with mapper');

        assert\same('B', values(['a' => 'A'], ['b' => 'B'])[1]);
    }

    public function testKeys(): void
    {
        assert\same([], keys(), 'args = 0');
        assert\same([], keys([]));
        assert\same([], keys([], new Map));
        assert\equals([0, 1, 2, 3, 4], keys(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\equals(
            [0, 'k', 1, 2],
            keys(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals([0, 20, 30, 40], _\toValues(keys(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 1 ? null : $value * 10;
        })), 'args > 1, with mapper');

        assert\equals([10], keys([10 => 'numeric']));
        assert\equals([10], keys([10 => 'numeric'], static function ($key) {
            return $key;
        }));
        assert\equals([0, 1], keys([10 => 'numeric'], [20 => 'numeric']));
    }

    public function testMixin(): void
    {
        assert\same([], mixin(), 'args = 0');
        assert\same([], mixin([]));
        assert\same([], mixin([], new Map));
        assert\equals(['d', 'b'], mixin(['a', 'b'], ['c'], ['d']), 'args > 1, no mapper');

        assert\equals(
            ['d', 'K' => 'c', 'k' => 'e'],
            mixin(['a', 'k' => 'b', 'K' => 'c'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals(['D'], _\toValues(mixin(['a', 'b'], ['c'], ['d'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');
    }

    public function testEveryFunction(): void
    {
        assert\true(every());
        assert\true(every([]));
        assert\true(every([], []));
        assert\true(every([' ']));
        assert\false(every(['']));
        assert\true(every([''], [0], static function() {return true;}));
    }

    public function testSomeFunction(): void
    {
        assert\false(some());
        assert\false(some([]));
        assert\false(some([''], [0, null], [[]]));
        assert\true(some([''], [0, null], [false, []], [' ']));
        assert\false(some([1, true], [' '], static function() {return false;}));
    }

    public function testTreeFunction(): void
    {
        assert\same([], tree());
        assert\same([], tree([], []));
        assert\same(['a', 'b', ['c'], 'c'], tree(['a'], ['b', ['c']]));
        assert\same(['A', 'B', 0, 'C'], tree(['a'], ['b', ['c']], static function($value, Map\Path $it) {
            return is_string($value) ? strtoupper($value) : $it->getDepth();
        }));
    }

    public function testLeavesFunction(): void
    {
        assert\same([], leaves());
        assert\same([], leaves([], []));
        assert\same(['a', 'b', 'c'], leaves(['a'], ['b', ['c']]));
        assert\same(['a', 'b', '  c'], leaves(['a'], ['b', ['c']], static function(Map\Path $it) {
            return (string) $it;
        }));
    }

    /**
     * @param mixed $value
     * @param mixed $key
     * @return string
     */
    public function __invoke($value, &$key): string
    {
        $key = strtolower($key);
        return strtoupper($value);
    }
}
