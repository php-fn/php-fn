<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use Exception;
use Php;
use Php\test\assert;
use PHPUnit\Framework\TestCase;
use RecursiveArrayIterator;
use Traversable;

class TreeTest extends TestCase
{
    public function providerRecursiveIteration(): array
    {
        return [
            'use mapped value to determinate children' => [
                'expected' => [(object)['a', 'b'], (object)['c', 'd']],
                'inner'    => [['a', 'b'], ['c', 'd']],
                'mapper'   => static function ($value) {
                    return (object)$value;
                },
            ],
            'mapper is only available on the first level' => [
                'expected' => ['A', 'b', 'C', 'd', 'e'],
                'inner' => ['a', ['b'], 'c', ['d', 'e']],
                'mapper' => static function ($value) {
                    return is_string($value) ? strtoupper($value) : $value;
                },
                'mode' => Path::LEAVES_ONLY
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
                'mapper' => static function () {
                    return Php::mapChildren(static function ($value, $key) {
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
                'mapper' => static function ($value, $key) {
                    $children = [];
                    for ($i = 0; $i <= $key; $i++) {
                        $children[] = "$value-$i";
                    }
                    return Php::mapChildren($children);
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
     * @param array                 $expected
     * @param iterable|Traversable $inner
     * @param callable|null         $mapper
     * @param int                   $mode
     */
    public function testRecursiveIteration($expected, $inner, $mapper, $mode = Path::SELF_FIRST): void
    {
        assert\equals($expected, Php::toValues(new Path(new Tree($inner, ...(array)$mapper), $mode)));
        assert\equals($expected, Php::toValues(new Path(new Tree(new Lazy(static function () use ($inner) {
            return is_array($inner) ? new RecursiveArrayIterator($inner) : $inner;
        }), ...(array)$mapper), $mode)));
    }

    public function providerSimpleIteration(): array
    {
        return [
            'combine map, skip and stop' => [
                'expected' => ['directly-key' => 'directly-value', 'map-key' => 'map-value', 3 => 'd'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => static function ($value, &$key) {
                    if ($value === 'e') {
                        return Php::mapBreak();
                    }
                    if ($value === 'c') {
                        return null;
                    }
                    if ($value === 'a') {
                        $key = 'directly-key';
                        return 'directly-value';
                    }
                    if ($value === 'b') {
                        return Php::mapValue('map-value', 'map-key');
                    }

                    return $value;
                },
            ],
            'stop iteration' => [
                'expected' => ['a', 'b', 'c'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => static function ($value) {
                    if ($value === 'd') {
                        return Php::mapBreak();
                    }
                    return $value;
                },
            ],
            'skip entries' => [
                'expected' => ['a', 3 => 'd', 5 => 'f'],
                'inner' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'mapper' => static function ($value) {
                    return in_array($value, ['b', 'c', 'e'], true) ? null : $value;
                },
            ],
            'map keys with Value object' => [
                'expected' => ['0-a' => 'a', '1-b' => 'b', '2-c' => 'c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => static function ($value, $key) {
                    return Php::mapKey("$key-$value");
                },
            ],
            'map keys directly' => [
                'expected' => ['0-a' => 'a', '1-b' => 'b', '2-c' => 'c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => static function ($value, &$key) {
                    $key = "$key-$value";
                    return $value;
                },
            ],
            'map values with Value object' => [
                'expected' => ['0-a', '1-b', '2-c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => static function ($value, $key) {
                    return Php::mapValue("$key-$value");
                },
            ],
            'map values directly' => [
                'expected' => ['0-a', '1-b', '2-c'],
                'inner' => ['a', 'b', 'c'],
                'mapper' => static function ($value, $key, Tree $iterator) {
                    self::assertInstanceOf(Tree::class, $iterator);
                    return "$key-$value";
                },
            ]
        ];
    }

    /**
     * @dataProvider providerSimpleIteration
     *
     * @param array|Exception $expected
     * @param iterable|Traversable $inner
     * @param callable $mapper
     */
    public function testSimpleIteration($expected, $inner, callable $mapper): void
    {
        assert\equals\trial($expected, static function ($iterator) {
            return Php::traverse($iterator);
        }, new Tree($inner, ...(array)$mapper));

        assert\equals\trial($expected, static function ($iterator) {
            return Php::traverse($iterator);
        }, new Tree(new Lazy(static function () use ($inner) {
            return $inner;
        }), ...(array)$mapper));
    }

    public function testRecursive(): void
    {
        $tree = new Tree(['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c']]);
        self::assertInstanceOf(Tree::class, $tree->recursive());
        assert\not\same($tree, $tree->recursive());

        assert\same(
            ['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c'], 'k2' => 'b', 'k3' => 'c'],
            Php::traverse($tree->recursive())
        );
        assert\same(
            ['k0' => ['a', 0], 'k1' => [['k2' => 'b', 'k3' => 'c'], 0], 'k3' => ['c', 1]],
            Php::traverse($tree->recursive(static function ($value, $key, Path $it) {
                return $value === 'b' ? null : Php::mapValue([$value, $it->getDepth()]);
            }))
        );
        assert\same(
            ['k0' => ['a', 0], 'k3' => ['c', 1]],
            Php::traverse($tree->flatten(static function ($value, $key, Path $it) {
                return $value === 'b' ? null : Php::mapValue([$value, $it->getDepth()]);
            }))
        );

        $tree = new Tree(['a' => 'a', new Tree(['b' => 'b', new Tree(['c' => 'c'])])]);
        assert\same(['a' => 'a', 'b' => 'b', 'c' => 'c'], Php::traverse($tree->flatten()));
        assert\same([0, 0, 1, 1, 2], Php::traverse($tree->recursive(static function ($value, $key, Path $it) {
            static $count = 0;
            return Php::mapValue($it->getDepth())->andKey($count++);
        })));
    }

    public function testMultipleMappers(): void
    {
        $tree = new Tree(
            ['k1' => 'v1', 'k2' => 'v2'],
            static function ($value, &$key) {
                $key = strtoupper($key);
                return $value === 'v1' ? Php::mapNull() : $value;
            },
            static function ($value, $key) {
                return Php::mapValue($value ?? '-')->andKey($key === 'K1' ? 'k1' : $key);
            },
            static function ($value) {
                return $value . $value;
            }
        );

        assert\same([
            'k1' => '--',
            'K2' => 'v2v2',
        ], Php::traverse($tree));
    }

    public function testIsLast(): void
    {
        assert\same(
            ['a' => false, 'b' => false, 'c' => true],
            Php::traverse(new Tree(['a', 'b', 'c'], static function ($value, $key, Tree $it) {
                return Php::mapKey($value)->andValue($it->isLast());
            }))
        );
    }
}
