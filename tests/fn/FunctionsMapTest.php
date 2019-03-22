<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\test\assert;

/**
 * @covers \fn\*
 */
class FunctionsMapTest extends MapTest
{
    /**
     * @inheritdoc
     */
    protected function map(...$arguments): Map
    {
        return map(...$arguments);
    }

    /**
     * @covers ::hasKey
     */
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

    /**
     * @covers ::hasValue
     */
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

    /**
     * @covers ::map
     */
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
                function($value, $key) {
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
                function() {
                    return mapValue(mapNull());
                }
            ],

            'fn\map should not merge single iterable' => [
                [2 => 'numeric-key'],
                [2 => 'numeric-key']
            ],
            'without Map\Value class' => [
                ['v1' => 'k1', 'v2' => 'k2'],
                ['k1' => 'v1', 'k2' => 'v2'],
                function ($value, &$key) {
                    $tmp = $key;
                    $key = $value;
                    return $tmp;
                }
            ],
            'using mapBreak() and mapNull()' => [
                [1 => null, 3 => 'd'],
                ['a', 'b', 'c', 'd', 'e', 'f'],
                function ($value) {
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
                function ($value, $key) {
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
                function ($value) {
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
                function($value, $key) {
                    return mapGroup($value)->andKey($key + 100);
                },
            ],
        ];
    }

    /**
     * @dataProvider providerSameBehaviourTraverseAndMap
     *
     * @covers ::traverse
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
     * @covers ::map
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourMap($expected, $iterable, ...$mapper): void
    {
        assert\same($expected, map($iterable, ...$mapper)->traverse);
    }

    /**
     * @covers ::traverse
     */
    public function testTraverse(): void
    {
        $emptyCallable = function () {};

        assert\same(['key' => 'value'], traverse(['key' => 'value']));
        assert\same(['key' => 'value'], traverse(new \ArrayObject(['key' => 'value'])));
        assert\same([], traverse(_\toArray(null, true)));
        assert\same([], traverse(_\toArray(null, true), $emptyCallable));

        assert\exception('argument $candidate must be traversable', function () {
            traverse(null);
        });
        assert\exception('argument $traversable must be traversable', function ($emptyCallable) {
            traverse(null, $emptyCallable);
        }, $emptyCallable);
        assert\same([1], traverse(_\toArray('value', true), 'count'));
        assert\same(['VALUE'], traverse(_\toArray('value', true), $this));
    }

    /**
     * @covers ::mapNull
     * @covers ::mapBreak
     */
    public function testNullBreak(): void
    {
        assert\equals((object)[], mapNull());
        assert\same(mapNull(), mapNull());

        assert\equals((object)[], mapBreak());
        assert\same(mapBreak(), mapBreak());

        assert\equals(mapBreak(), mapNull());
        assert\not\same(mapBreak(), mapNull());
    }

    /**
     * @covers ::mapValue
     * @covers ::mapKey
     * @covers ::mapGroup
     * @covers ::mapChildren
     */
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

    /**
     * @covers ::map
     */
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
        assert\equals(['A', 'C', 'D', 'E'], map(['a', 'b'], ['c'], ['d', 'e'], function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })->values, 'args > 1, with mapper');

        // array_key_exists('a', array_merge(['a' => 'A'], ['b' => 'B']))
        assert\true(isset(map(['a' => null], ['b' => 'B'])['a']));

        // in_array('B', array_merge(['a' => 'A'], ['b' => 'B']), true)
        assert\true(map(['a' => null], ['b' => 'B'])->has('B'));

        // array_merge(['a' => 'A'], ['b' => 'B'])['b']
        assert\same('B', map(['a' => 'A'], ['b' => 'B'])['b']);
    }

    /**
     * @covers ::merge
     */
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

        assert\equals(['A', 'C', 'D', 'E'], _\toValues(merge(['a', 'b'], ['c'], ['d', 'e'], function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');

        assert\same('B', merge(['a' => 'A'], ['b' => 'B'])['b']);
    }

    /**
     * @covers ::values
     */
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

        assert\same(['A', 2 => 'C', 'D', 'E'], values(['a', 'b'], ['c'], ['d', 'e'], function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        }), 'args > 1, with mapper');

        assert\same('B', values(['a' => 'A'], ['b' => 'B'])[1]);
    }

    /**
     * @covers ::keys
     */
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

        assert\equals([0, 20, 30, 40], _\toValues(keys(['a', 'b'], ['c'], ['d', 'e'], function ($value) {
            return $value === 1 ? null : $value * 10;
        })), 'args > 1, with mapper');

        assert\equals([10], keys([10 => 'numeric']));
        assert\equals([10], keys([10 => 'numeric'], function($key) {
            return $key;
        }));
        assert\equals([0, 1], keys([10 => 'numeric'], [20 => 'numeric']));
    }

    /**
     * @covers ::mixin
     */
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

        assert\equals(['D'], _\toValues(mixin(['a', 'b'], ['c'], ['d'], function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');
    }

    /**
     * @covers ::every
     */
    public function testEveryFunction(): void
    {
        assert\true(every());
        assert\true(every([]));
        assert\true(every([], []));
        assert\true(every([' ']));
        assert\false(every(['']));
        assert\true(every([''], [0], function() {return true;}));
    }

    /**
     * @covers ::some
     */
    public function testSomeFunction(): void
    {
        assert\false(some());
        assert\false(some([]));
        assert\false(some([''], [0, null], [[]]));
        assert\true(some([''], [0, null], [false, []], [' ']));
        assert\false(some([1, true], [' '], function() {return false;}));
    }

    /**
     * @covers ::tree
     */
    public function testTreeFunction(): void
    {
        assert\same([], tree());
        assert\same([], tree([], []));
        assert\same(['a', 'b', ['c'], 'c'], tree(['a'], ['b', ['c']]));
        assert\same(['C', 'B', 0], tree(['a'], ['b', ['c']], function($value, Map\Path $it) {
            return is_string($value) ? strtoupper($value) : $it->getDepth();
        }));
    }

    /**
     * @covers ::leaves
     */
    public function testLeavesFunction(): void
    {
        assert\same([], leaves());
        assert\same([], leaves([], []));
        assert\same(['a', 'b', 'c'], leaves(['a'], ['b', ['c']]));
        assert\same(['C', 'B'], leaves(['a'], ['b', ['c']], function($value, Map\Path $it) {
            return is_string($value) ? strtoupper($value) : $it->getDepth();
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
