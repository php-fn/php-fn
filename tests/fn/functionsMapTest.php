<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use fn\test\assert;

/**
 * @covers \fn\*
 */
class functionsMapTest extends MapTest
{
    /**
     * @inheritdoc
     */
    protected function map(...$arguments)
    {
        return map(...$arguments);
    }

    /**
     * @covers \fn\hasKey()
     */
    public function testHasKey()
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
     * @covers \fn\hasValue()
     */
    public function testHasValue()
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
     * @covers \fn\map()
     */
    public function testMapReplace()
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
     * @covers \fn\toIterable()
     */
    public function testToIterable()
    {
        $ar = [true];
        $it = new \ArrayObject($ar);
        assert\same($ar, toIterable([true]));
        assert\same($it, toIterable($it));
        assert\equals($it, toIterable(new \ArrayObject($ar)));
        assert\not\same($it, toIterable(new \ArrayObject($ar)));
        assert\same(['string'], toIterable('string', true));
        assert\same([], toIterable(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toIterable('string');
        });
        assert\same(null, toIterable('string', false, false));

        $result = toIterable('string', false, function ($candidate, \InvalidArgumentException $e) {
            assert\same('string', $candidate);
            return $e;
        });

        assert\type(\InvalidArgumentException::class, $result);
    }

    /**
     * @covers \fn\toMap()
     */
    public function testToMap()
    {
        assert\equals(['key' => 'value'], toMap(['key' => 'value']));
        assert\equals(['key' => 'value'], toMap(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toMap(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toMap(null);
        });
    }

    /**
     * @covers \fn\toValues()
     */
    public function testToValues()
    {
        assert\equals(['value'], toValues(['key' => 'value']));
        assert\equals(['value'], toValues(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toValues(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toValues(null);
        });
    }

    /**
     * @return array[]
     */
    public function providerSameBehaviourTraverseAndMap()
    {
        $toGroup = [['a', 'a'], ['a', 'b'], ['b', 'b'], ['b', 'a']];

        return [
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
     * @covers \fn\traverse()
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourTraverse($expected, $iterable, $mapper)
    {
        assert\same($expected, traverse($iterable, $mapper));
    }

    /**
     * @dataProvider providerSameBehaviourTraverseAndMap
     *
     * @covers \fn\map()
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourMap($expected, $iterable, $mapper)
    {
        assert\same($expected, map($iterable, $mapper)->map);
    }

    /**
     * @covers \fn\traverse()
     */
    public function testTraverse()
    {
        $emptyCallable = function () {};
        $message = 'argument $iterable must be iterable';

        assert\same(['key' => 'value'], traverse(['key' => 'value']));
        assert\same(['key' => 'value'], traverse(new \ArrayObject(['key' => 'value'])));
        assert\same([], traverse(toMap(null, true)));
        assert\same([], traverse(toMap(null, true), $emptyCallable));

        assert\exception($message, function () {
            traverse(null);
        });
        assert\exception($message, function ($emptyCallable) {
            traverse(null, $emptyCallable);
        }, $emptyCallable);
        assert\same([1], traverse(toMap('value', true), 'count'));
        assert\same(['VALUE'], traverse(toMap('value', true), $this));
    }

    /**
     * @covers \fn\mapNull()
     * @covers \fn\mapBreak()
     */
    public function testNullBreak()
    {
        assert\equals((object)[], mapNull());
        assert\same(mapNull(), mapNull());

        assert\equals((object)[], mapBreak());
        assert\same(mapBreak(), mapBreak());

        assert\equals(mapBreak(), mapNull());
        assert\not\same(mapBreak(), mapNull());
    }

    /**
     * @covers \fn\mapValue()
     * @covers \fn\mapKey()
     * @covers \fn\mapGroup()
     * @covers \fn\mapChildren()
     */
    public function testValueFunctions()
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
     * @covers \fn\map()
     */
    public function testMap()
    {
        assert\type(Map::class, map());
        assert\equals([], map()->map, 'args = 0');
        assert\equals(
            ['a', 'b', 'c', 'd', 'e'],
            map(['a', 'b'], ['c'], ['d', 'e'])->map,
            'args > 1, no mapper'
        );
        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            map(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e'])->map,
            'args > 1, no mapper, with assoc key'
        );
        assert\equals(['A', 'C', 'D', 'E'], map(['a', 'b'], ['c'], ['d', 'e'], function($value) {
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
     * @param mixed $value
     * @param mixed $key
     * @return string
     */
    public function __invoke($value, &$key)
    {
        $key = strtolower($key);
        return strtoupper($value);
    }
}
