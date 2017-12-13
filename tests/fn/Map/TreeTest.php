<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use ArrayIterator;
use fn;
use fn\test\assert;
use PHPUnit_Framework_TestCase;
use RecursiveArrayIterator;
use RecursiveIteratorIterator as Rec;
use RuntimeException;

/**
 * @covers Tree
 */
class TreeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function providerRecursiveIteration()
    {
        return [
            'inner iterator is recursive' => [
                'expected' => ['a', ['a-0'], 'a-0', 'b', ['b-0', 'b-1'], 'b-0', 'b-1'],
                'inner' => new RecursiveArrayIterator(['a', ['a-0'], 'b', ['b-0', 'b-1']]),
                'mapper' => null,
            ],
            'Map::andChildren with callable' => [
                'expected' => ['a', 'a-0', 'b', 'b-0', 'b-1', 'c', 'c-0', 'c-1', 'c-2'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function () {
                    return fn\map\children(function ($value, $key) {
                        $children = [];
                        for ($i = 0; $i <= $key; $i++) {
                            $children[] = "$value-$i";
                        }
                        return $children;
                    });
                },
            ],
            'Map::andChildren with array' => [
                'expected' => ['a', 'a-0', 'b', 'b-0', 'b-1', 'c', 'c-0', 'c-1', 'c-2'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function ($value, $key) {
                    $children = [];
                    for ($i = 0; $i <= $key; $i++) {
                        $children[] = "$value-$i";
                    }
                    return fn\map\children($children);
                },
            ],
            'no mapper' => [
                'expected' => [],
                'inner' => [],
                'mapper' => null,
            ],
        ];
    }

    /**
     * @dataProvider providerRecursiveIteration
     *
     * @covers       Tree::getChildren
     * @covers       Tree::hasChildren
     * @covers       Tree::doMap
     *
     * @param array $expected
     * @param iterable|\Traversable $inner
     * @param callable|null $mapper
     */
    public function testRecursiveIteration($expected, $inner, $mapper)
    {
        assert\equals($expected, fn\toValues(new Rec(new Tree($inner, $mapper), Rec::SELF_FIRST)));
        assert\equals($expected, fn\toValues(new Rec(new Tree(new Lazy(function() use($inner) {
            return $inner;
        }), $mapper), Rec::SELF_FIRST)));
    }

    /**
     * @return array
     */
    public function providerSimpleIteration()
    {
        return [
            'combine map, skip and stop' => [
                'expected' => ['directly-key' => 'directly-value', 'map-key' => 'map-value', 3 => 'd'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => function ($value, &$key) {
                    if ($value === 'e') {
                        return fn\map\stop();
                    }
                    if ($value === 'c') {
                        return null;
                    }
                    if ($value === 'a') {
                        $key = 'directly-key';
                        return 'directly-value';
                    }
                    if ($value === 'b') {
                        return fn\map\value('map-value', 'map-key');
                    }

                    return $value;
                },
            ],
            'stop iteration' => [
                'expected' => ['a', 'b', 'c'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => function ($value) {
                    if ($value === 'd') {
                        return fn\map\stop();
                    }
                    return $value;
                },
            ],
            'skip entries' => [
                'expected' => ['a', 3 => 'd', 5 => 'f'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => function ($value) {
                    return in_array($value, ['b', 'c', 'e'], true) ? null : $value;
                },
            ],
            'map keys with Value object' => [
                'expected' => ['0-a' => 'a', '1-b' => 'b', '2-c' => 'c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function ($value, $key) {
                    return fn\map\key("$key-$value");
                },
            ],
            'map keys directly' => [
                'expected' => ['0-a' => 'a', '1-b' => 'b', '2-c' => 'c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function ($value, &$key) {
                    $key = "$key-$value";
                    return $value;
                },
            ],
            'map values with Value object' => [
                'expected' => ['0-a', '1-b', '2-c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function ($value, $key) {
                    return fn\map\value("$key-$value");
                },
            ],
            'map values directly' => [
                'expected' => ['0-a', '1-b', '2-c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function ($value, $key, Tree $iterator) {
                    assert\type(Tree::class, $iterator);
                    return "$key-$value";
                },
            ],
            'simple iterator' => [
                'expected' => ['a' => 'a', 'b' => ['c' => 'd']],
                'inner' => new ArrayIterator(['a' => 'a', 'b' => ['c' => 'd']]),
                'mapper' => null,
            ],
            'simple array' => [
                'expected' => ['a', 'b', 'c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => null,
            ],
            'empty array' => [
                'expected' => [],
                'inner' => [],
                'mapper' => null,
            ],
            'null => exception' => [
                'expected' => new RuntimeException('Property $inner must be iterable'),
                'inner' => null,
                'mapper' => null,
            ],
        ];
    }

    /**
     * @dataProvider providerSimpleIteration
     *
     * @covers       Tree::rewind
     * @covers       Tree::valid
     * @covers       Tree::key
     * @covers       Tree::current
     * @covers       Tree::next
     * @covers       Tree::doMap
     *
     * @param array|\Exception $expected
     * @param iterable|\Traversable $inner
     * @param callable|null $mapper
     */
    public function testSimpleIteration($expected, $inner, $mapper)
    {
        assert\equals\trial($expected, function($iterator) {
            return fn\traverse($iterator);
        }, new Tree($inner, $mapper));

        assert\equals\trial($expected, function($iterator) {
            return fn\traverse($iterator);
        }, new Tree(new Lazy(function() use($inner) {
            return $inner;
        }), $mapper));
    }
}
