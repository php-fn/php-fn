<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use fn\test\assert;
use stdClass;

/**
 * @covers map\*
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
     * @covers hasKey()
     */
    public function testHasKey()
    {
        assert\false(hasKey('key', null));
        assert\false(hasKey('key', new stdClass));
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
     * @covers hasValue()
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
     * @covers map()
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
     * @covers toIterable()
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
        assert\exception('Argument $candidate must be iterable', function () {
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
     * @covers toMap()
     */
    public function testToMap()
    {
        assert\equals(['key' => 'value'], toMap(['key' => 'value']));
        assert\equals(['key' => 'value'], toMap(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toMap(null, true));
        assert\exception('Argument $candidate must be iterable', function () {
            toMap(null);
        });
    }

    /**
     * @covers toValues()
     */
    public function testToValues()
    {
        assert\equals(['value'], toValues(['key' => 'value']));
        assert\equals(['value'], toValues(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toValues(null, true));
        assert\exception('Argument $candidate must be iterable', function () {
            toValues(null);
        });
    }

    /**
     * @covers traverse()
     */
    public function testTraverse()
    {
        $emptyCallable = function () {
        };
        $message = 'Argument $candidate must be iterable';

        assert\same(['key' => 'value'], traverse(['key' => 'value']));
        assert\same(['key' => 'value'], traverse(new \ArrayObject(['key' => 'value'])));
        assert\same([], traverse(null, true));
        assert\same([], traverse(null, $emptyCallable, true));

        assert\exception($message, function () {
            traverse(null);
        });
        assert\exception($message, function () {
            traverse(null, false);
        });
        assert\exception($message, function ($emptyCallable) {
            traverse(null, $emptyCallable);
        }, $emptyCallable);
        assert\exception($message, function ($emptyCallable) {
            traverse(null, $emptyCallable, false);
        }, $emptyCallable);

        assert\same(['v1' => 'k1', 'v2' => 'k2'], traverse(['k1' => 'v1', 'k2' => 'v2'], function ($value, &$key) {
            $tmp = $key;
            $key = $value;
            return $tmp;
        }));

        assert\same([1 => null, 3 => 'd'], traverse(['a', 'b', 'c', 'd', 'e', 'f'], function ($value) {
            if ($value === 'e') {
                return mapBreak();
            }
            if (in_array($value, ['a', 'c'], true)) {
                return null;
            }
            return $value === 'b' ? mapNull() : $value;
        }));

        assert\same([1], traverse('value', 'count', true));
        assert\same(['VALUE'], traverse('value', $this, true));

        assert\same(
            ['VALUE', 'KEY' => 'key', 'pair' => 'flip', 'no' => 'changes'],
            traverse(['value', 'key', 'flip' => 'pair', 'no' => 'changes'], function($value, $key) {
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
            }
        ));

        $toGroup = [['a', 'a'], ['a', 'b'], ['b', 'b'], ['b', 'a']];
        assert\same(
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
            traverse($toGroup, function($value) {
                return mapGroup($value[0]);
            }),
            'group by a single value'
        );

        assert\same(
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
            traverse($toGroup, function($value, $key) {
                return mapGroup($value)->andKey($key + 100);
            }),
            'group by multiple values, with key  '
        );
    }

    /**
     * @covers map\null()
     * @covers map\stop()
     */
    public function testNullStop()
    {
        assert\equals(new Map\Value, mapNull());
        assert\same(mapNull(), mapNull());

        assert\equals(new Map\Value, mapBreak());
        assert\same(mapBreak(), mapBreak());

        assert\equals(mapBreak(), mapNull());
        assert\not\same(mapBreak(), mapNull());
    }

    /**
     * @covers map\value()
     * @covers map\key()
     * @covers map\children()
     */
    public function testValueKeyChildren()
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
