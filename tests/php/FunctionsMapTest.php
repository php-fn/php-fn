<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayObject;
use Php\test\assert;

class FunctionsMapTest extends MapTest
{
    /**
     * @inheritdoc
     */
    protected function map(...$arguments): Map
    {
        return Php::map(...$arguments);
    }

    public function testHasKey(): void
    {
        assert\false(Php::hasKey('key', null));
        assert\false(Php::hasKey('key', (object)[]));
        assert\false(Php::hasKey('key', []));
        assert\false(Php::hasKey('key', Php::map()));

        assert\true(Php::hasKey('key', Php::map(['key' => null])));
        assert\true(Php::hasKey('key', ['key' => null]));

        assert\true(Php::hasKey('key', Php::map(['key' => false])));
        assert\true(Php::hasKey('key', ['key' => false]));

        assert\true(Php::hasKey('key', Php::map(['key' => 0])));
        assert\true(Php::hasKey('key', ['key' => 0]));
        assert\true(Php::hasKey('key', ['key' => 0]));

        assert\true(Php::hasKey(0, 'a'));
        assert\false(Php::hasKey(0, ''));
    }

    public function testHasValue(): void
    {
        assert\false(Php::hasValue('value', null));
        assert\false(Php::hasValue('value', []));
        assert\false(Php::hasValue('value', Php::map()));

        assert\true(Php::hasValue(100, [100]));
        assert\false(Php::hasValue('100', [100]));
        assert\true(Php::hasValue('100', [100], false));

        assert\true(Php::hasValue(100, Php::map([100])));
        assert\false(Php::hasValue('100', Php::map([100])));
        assert\true(Php::hasValue('100', Php::map([100]), false));
    }

    public function testMapReplace(): void
    {
        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            Php::traverse(Php::map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A']
            )),
            'two iterable arguments => replace'
        );

        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'c', 'd'],
            Php::traverse(Php::map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                Php::map(['c' => 'c', 'd'])
            )),
            'three iterable arguments => replace'
        );

        assert\same(
            ['k:a' => 'v:A', 'k:b' => 'v:b', 'k:c' => 'v:C'],
            Php::traverse(Php::map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                static function ($value, $key) {
                    return Php::mapValue("v:$value")->andKey("k:$key");
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
                    return Php::mapValue(Php::mapNull());
                }
            ],

            'Php\map should not merge single iterable' => [
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
                        return Php::mapBreak();
                    }
                    if (in_array($value, ['a', 'c'], true)) {
                        return null;
                    }

                    return $value === 'b' ? Php::mapNull() : $value;
                },
            ],
            'using mapValue() and mapKey()' => [
                ['VALUE', 'KEY' => 'key', 'pair' => 'flip', 'no' => 'changes'],
                ['value', 'key', 'flip' => 'pair', 'no' => 'changes'],
                static function ($value, $key) {
                    if ($value === 'value') {
                        return Php::mapValue('VALUE');
                    }
                    if ($value === 'key') {
                        return Php::mapKey('KEY');
                    }
                    if ($key === 'flip') {
                        return Php::mapValue($key)->andKey($value);
                    }

                    return Php::mapValue();
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
                    return Php::mapGroup($value[0]);
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
                    return Php::mapGroup($value)->andKey($key + 100);
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
        assert\same($expected, Php::traverse($iterable, ...$mapper));
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
        assert\same($expected, Php::map($iterable, ...$mapper)->traverse);
    }

    public function testTraverse(): void
    {
        $emptyCallable = static function () {};

        assert\same(['key' => 'value'], Php::traverse(['key' => 'value']));
        assert\same(['key' => 'value'], Php::traverse(new ArrayObject(['key' => 'value'])));
        assert\same([], Php::traverse(Php::toArray(null, true)));
        assert\same([], Php::traverse(Php::toArray(null, true), $emptyCallable));

        assert\exception('argument $candidate must be traversable', static function () {
            Php::traverse(null);
        });
        assert\exception('argument $traversable must be traversable', static function ($emptyCallable) {
            Php::traverse(null, $emptyCallable);
        }, $emptyCallable);
        assert\same(['VALUE'], Php::traverse(Php::toArray('value', true), $this));

        if (PHP_VERSION_ID < 70200) {
            assert\same([1], Php::traverse(Php::toArray('value', true), 'count'));
        }
    }

    public function testNullBreak(): void
    {
        assert\equals((object)[], Php::mapNull());
        assert\same(Php::mapNull(), Php::mapNull());

        assert\equals((object)[], Php::mapBreak());
        assert\same(Php::mapBreak(), Php::mapBreak());

        assert\equals(Php::mapBreak(), Php::mapNull());
        assert\not\same(Php::mapBreak(), Php::mapNull());
    }

    public function testValueFunctions(): void
    {
        assert\equals(new Map\Value, Php::mapValue());
        assert\equals(new Map\Value('v'), Php::mapValue('v'));
        assert\equals(new Map\Value('v', 'k'), Php::mapValue('v', 'k'));
        assert\equals(new Map\Value('v', 'k', 'g'), Php::mapValue('v', 'k', 'g'));
        assert\equals(new Map\Value('v', 'k', 'g', 'c'), Php::mapValue('v', 'k', 'g', 'c'));

        assert\equals((new Map\Value)->andKey('k'), Php::mapKey('k'));
        assert\equals((new Map\Value)->andGroup('g'), Php::mapGroup('g'));
        assert\equals((new Map\Value)->andChildren('c'), Php::mapChildren('c'));
    }

    public function testMap(): void
    {
        assert\type(Map::class, Php::map());
        assert\equals([], Php::map()->traverse, 'args = 0');
        assert\equals(
            ['a', 'b', 'c', 'd', 'e'],
            Php::map(['a', 'b'], ['c'], ['d', 'e'])->traverse,
            'args > 1, no mapper'
        );
        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            Php::map(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e'])->traverse,
            'args > 1, no mapper, with assoc key'
        );
        assert\equals(['A', 'C', 'D', 'E'], Php::map(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })->values, 'args > 1, with mapper');

        // array_key_exists('a', array_merge(['a' => 'A'], ['b' => 'B']))
        assert\true(isset(Php::map(['a' => null], ['b' => 'B'])['a']));

        // array_merge(['a' => 'A'], ['b' => 'B'])['b']
        assert\same('B', Php::map(['a' => 'A'], ['b' => 'B'])['b']);
    }

    public function testMerge(): void
    {
        assert\same([], Php::merge(), 'args = 0');
        assert\same([], Php::merge([]));
        assert\same([], Php::merge([], new Map));
        assert\equals(['a', 'b', 'c', 'd', 'e'], Php::merge(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            Php::merge(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals(['A', 'C', 'D', 'E'], Php::toValues(Php::merge(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');

        assert\same('B', Php::merge(['a' => 'A'], ['b' => 'B'])['b']);
    }

    public function testValues(): void
    {
        assert\same([], Php::values(), 'args = 0');
        assert\same([], Php::values([]));
        assert\same([], Php::values([], new Map));
        assert\same(['a', 'b', 'c', 'd', 'e'], Php::merge(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\same(
            ['a', 'b', 'c', 'd', 'e'],
            Php::values(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\same(['A', 2 => 'C', 'D', 'E'], Php::values(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        }), 'args > 1, with mapper');

        assert\same('B', Php::values(['a' => 'A'], ['b' => 'B'])[1]);
    }

    public function testKeys(): void
    {
        assert\same([], Php::keys(), 'args = 0');
        assert\same([], Php::keys([]));
        assert\same([], Php::keys([], new Map));
        assert\equals([0, 1, 2, 3, 4], Php::keys(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\equals(
            [0, 'k', 1, 2],
            Php::keys(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals([0, 20, 30, 40], Php::toValues(Php::keys(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 1 ? null : $value * 10;
        })), 'args > 1, with mapper');

        assert\equals([10], Php::keys([10 => 'numeric']));
        assert\equals([10], Php::keys([10 => 'numeric'], static function ($key) {
            return $key;
        }));
        assert\equals([0, 1], Php::keys([10 => 'numeric'], [20 => 'numeric']));
    }

    public function testMixin(): void
    {
        assert\same([], Php::mixin(), 'args = 0');
        assert\same([], Php::mixin([]));
        assert\same([], Php::mixin([], new Map));
        assert\equals(['d', 'b'], Php::mixin(['a', 'b'], ['c'], ['d']), 'args > 1, no mapper');

        assert\equals(
            ['d', 'K' => 'c', 'k' => 'e'],
            Php::mixin(['a', 'k' => 'b', 'K' => 'c'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals(['D'], Php::toValues(Php::mixin(['a', 'b'], ['c'], ['d'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');
    }

    public function testEveryFunction(): void
    {
        assert\true(Php::every());
        assert\true(Php::every([]));
        assert\true(Php::every([], []));
        assert\true(Php::every([' ']));
        assert\false(Php::every(['']));
        assert\true(Php::every([''], [0], static function () {
            return true;
        }));
    }

    public function testSomeFunction(): void
    {
        assert\false(Php::some());
        assert\false(Php::some([]));
        assert\false(Php::some([''], [0, null], [[]]));
        assert\true(Php::some([''], [0, null], [false, []], [' ']));
        assert\false(Php::some([1, true], [' '], static function () {
            return false;
        }));
    }

    public function testTreeFunction(): void
    {
        assert\same([], Php::tree());
        assert\same([], Php::tree([], []));
        assert\same(['a', 'b', ['c'], 'c'], Php::tree(['a'], ['b', ['c']]));
        assert\same(['A', 'B', 0, 'C'], Php::tree(['a'], ['b', ['c']], static function ($value, Map\Path $it) {
            return is_string($value) ? strtoupper($value) : $it->getDepth();
        }));
    }

    public function testLeavesFunction(): void
    {
        assert\same([], Php::leaves());
        assert\same([], Php::leaves([], []));
        assert\same(['a', 'b', 'c'], Php::leaves(['a'], ['b', ['c']]));
        assert\same(['a', 'b', '  c'], Php::leaves(['a'], ['b', ['c']], static function (Map\Path $it) {
            return (string)$it;
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
