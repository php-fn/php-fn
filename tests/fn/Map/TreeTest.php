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
use SimpleXMLElement;

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
            'mapper is only available on the first level' => [
                'expected' => ['A', 'b', 'C', 'd', 'e'],
                'inner' => ['a', ['b'], 'c', ['d', 'e']],
                'mapper' => function($value) {
                    return is_string($value) ? strtoupper($value) : $value;
                },
                'mode' => Rec::LEAVES_ONLY
            ],
            'inner iterator is recursive array' => [
                'expected' => ['a', ['a-0'], 'a-0', 'b', ['b-0', 'b-1'], 'b-0', 'b-1'],
                'inner' => ['a', ['a-0'], 'b', ['b-0', 'b-1']],
                'mapper' => null,
            ],
            'inner iterator is RecursiveArrayIterator' => [
                'expected' => ['a', ['a-0'], 'a-0', 'b', ['b-0', 'b-1'], 'b-0', 'b-1'],
                'inner' => new RecursiveArrayIterator(['a', ['a-0'], 'b', ['b-0', 'b-1']]),
                'mapper' => null,
            ],
            'Map::andChildren with callable' => [
                'expected' => ['a', 'a-0', 'b', 'b-0', 'b-1', 'c', 'c-0', 'c-1', 'c-2'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => function () {
                    return fn\mapChildren(function ($value, $key) {
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
                    return fn\mapChildren($children);
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
    public function testRecursiveIteration($expected, $inner, $mapper, $mode = Rec::SELF_FIRST)
    {
        assert\equals($expected, fn\_\toValues(new Rec(new Tree($inner, ...(array)$mapper), $mode)));
        assert\equals($expected, fn\_\toValues(new Rec(new Tree(new Lazy(function () use ($inner) {
            return is_array($inner) ? new RecursiveArrayIterator($inner) : $inner;
        }), ...(array)$mapper), $mode)));
    }

    /**
     * @return array
     */
    public function providerSimpleIteration()
    {
        $ref = null;
        return [
            'intern traversable classes are wrapped around IteratorIterator' => [
                'expected' => [],
                'inner' => new Lazy(function() {
                    return new SimpleXMLElement('<root/>');
                }),
            ],
            '$inner::getIterator returns same instance' => [
                'expected' => new RuntimeException('Implementation $inner::getIterator returns same instance'),
                'inner' => $ref = new Lazy(function() use(&$ref) {
                    return $ref;
                }),
            ],
            '$inner::getIterator is too deep' => [
                'expected' => new RuntimeException('$inner::getIterator is too deep'),
                'inner' => new Lazy(function() {
                    return new Lazy(function() {
                        return new Lazy(function() {
                            return new Lazy(function() {
                                return new Lazy(function() {
                                    return new Lazy(function() {
                                        return new Lazy(function() {
                                            return new Lazy(function() {
                                                return new Lazy(function() {
                                                    return new Lazy(function() {
                                                        return new Lazy(function() {
                                                            return new Lazy(function() {
                                                            });
                                                        });
                                                    });
                                                });
                                            });
                                        });
                                    });
                                });
                            });
                        });
                    });
                }),
            ],
            '$inner depth = 3' => [
                'expected' => ['depth' => 3],
                'inner' => new Lazy(function() {
                    return new Lazy(function() {
                        return new Lazy(function() {
                            return ['depth' => 3];
                        });
                    });
                }),
            ],
            'combine map, skip and stop' => [
                'expected' => ['directly-key' => 'directly-value', 'map-key' => 'map-value', 3 => 'd'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => function ($value, &$key) {
                    if ($value === 'e') {
                        return fn\mapBreak();
                    }
                    if ($value === 'c') {
                        return null;
                    }
                    if ($value === 'a') {
                        $key = 'directly-key';
                        return 'directly-value';
                    }
                    if ($value === 'b') {
                        return fn\mapValue('map-value', 'map-key');
                    }

                    return $value;
                },
            ],
            'stop iteration' => [
                'expected' => ['a', 'b', 'c'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => function ($value) {
                    if ($value === 'd') {
                        return fn\mapBreak();
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
                    return fn\mapKey("$key-$value");
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
                    return fn\mapValue("$key-$value");
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
            ],
            'simple array' => [
                'expected' => ['a', 'b', 'c'],
                'inner' => ['a', 'b', 'c'],
            ],
            'empty array' => [
                'expected' => [],
                'inner' => [],
            ],
            'null => exception' => [
                'expected' => new RuntimeException('Property $inner must be iterable'),
                'inner' => null,
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
    public function testSimpleIteration($expected, $inner, $mapper = null)
    {
        assert\equals\trial($expected, function ($iterator) {
            return fn\traverse($iterator);
        }, new Tree($inner, ...(array)$mapper));

        assert\equals\trial($expected, function ($iterator) {
            return fn\traverse($iterator);
        }, new Tree(new Lazy(function () use ($inner) {
            return $inner;
        }), ...(array)$mapper));
    }

    /**
     * @covers Tree::recursive
     * @covers Tree::flatten
     */
    public function testRecursive()
    {
        $tree = new Tree(['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c']]);
        assert\type(Tree::class, $tree->recursive());
        assert\not\same($tree, $tree->recursive());

        assert\same(
            ['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c'], 'k2' => 'b', 'k3' => 'c'],
            fn\traverse($tree->recursive())
        );
        assert\same(
            ['k0' => ['a', 0], 'k1' => [['k2' => 'b', 'k3' => 'c'], 0], 'k3' => ['c', 1]],
            fn\traverse($tree->recursive(function($value, $key, Rec $it) {
                return $value === 'b' ? null : fn\mapValue([$value, $it->getDepth()]);
            }))
        );
        assert\same(
            ['k0' => ['a', 0], 'k3' => ['c', 1]],
            fn\traverse($tree->flatten(function($value, $key, Rec $it) {
                return $value === 'b' ? null : fn\mapValue([$value, $it->getDepth()]);
            }))
        );

        $tree = new Tree(['a' => 'a', new Tree(['b' => 'b', new Tree(['c' => 'c'])])]);
        assert\same(['a' => 'a', 'b' => 'b', 'c' => 'c'], fn\traverse($tree->flatten()));
        assert\same([0, 0, 1, 1, 2], fn\traverse($tree->recursive(function($value, $key, Rec $it) {
            static $count = 0;
            return fn\mapValue($it->getDepth())->andKey($count++);
        })));
    }

    /**
     */
    public function testMultipleMappers()
    {
        $tree = new Tree(
            ['k1' => 'v1', 'k2' => 'v2'],
            function($value, &$key) {
                $key = strtoupper($key);
                return $value === 'v1' ? fn\mapNull() : $value;
            },
            function($value, $key) {
                return fn\mapValue($value === null ? '-' : $value)->andKey($key === 'K1' ? 'k1' : $key);
            },
            function($value) {
                return $value . $value;
            }
        );

        assert\same([
            'k1' => '--',
            'K2' => 'v2v2',
        ], fn\traverse($tree));
    }
}
