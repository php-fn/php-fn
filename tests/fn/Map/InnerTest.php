<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use ArrayIterator;
use fn;
use fn\test\assert;
use RuntimeException;
use SimpleXMLElement;

/**
 * @covers Inner
 */
class InnerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    public function providerUnify(): array
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
     * @dataProvider providerUnify
     *
     * @covers       Inner::unify
     *
     * @param array|\Exception $expected
     * @param iterable|\Traversable $inner
     */
    public function testUnify($expected, $inner): void
    {
        assert\equals\trial($expected, function ($inner) {
            return fn\traverse(new Inner($inner));
        }, $inner);

        assert\equals\trial($expected, function ($inner) {
            return fn\traverse(
                new Inner(new Lazy(function () use ($inner) {
                    return $inner;
                }))
            );
        }, $inner);
    }

    /**
     * @covers Inner::isLast
     */
    public function testIsLastExplicitIteration(): void
    {
        $it = new Inner([]);
        assert\same(null, $it->isLast());
        $it->rewind();
        assert\same(null, $it->isLast());

        $it = new Inner(['a', 'b', 'c']);

        assert\false($it->valid());
        assert\same(null, $it->current());
        assert\same(null, $it->key());
        assert\same(null, $it->isLast());
        assert\false($it->valid());
        assert\same(null, $it->current());
        assert\same(null, $it->key());
        assert\same(null, $it->isLast());

        $it->rewind();
        assert\same(0, $it->key());
        assert\same('a', $it->current());
        assert\true($it->valid());
        assert\false($it->isLast());
        assert\true($it->valid());
        assert\same('a', $it->current());
        assert\same(0, $it->key());
        assert\false($it->isLast());
        $it->next();
        assert\same('b', $it->current());
        assert\same(1, $it->key());
        assert\false($it->isLast());
        assert\true($it->valid());
        assert\false($it->isLast());
        assert\same('b', $it->current());
        assert\same(1, $it->key());
        $it->next();
        assert\true($it->valid());
        assert\true($it->isLast());
        assert\same('c', $it->current());
        assert\same(2, $it->key());
        assert\same(2, $it->key());
        assert\same('c', $it->current());
        assert\true($it->isLast());
        $it->next();
        assert\false($it->valid());
        assert\same(null, $it->current());
        assert\same(null, $it->key());
        assert\same(null, $it->isLast());
        assert\false($it->valid());
        assert\same(null, $it->current());
        assert\same(null, $it->key());
        assert\same(null, $it->isLast());

        $it = new Inner(['a']);
        assert\same(null, $it->isLast());
        $it->rewind();
        assert\true($it->isLast());
        assert\same(0, $it->key());
        assert\same('a', $it->current());
        assert\true($it->valid());
        assert\true($it->isLast());
        $it->next();
        assert\false($it->valid());
        assert\same(null, $it->current());
        assert\same(null, $it->key());
        assert\same(null, $it->isLast());
    }

    /**
     * @return array
     */
    public function providerIsLast(): array
    {
        return [
            'empty'    => [[], new Inner([])],
            'single'   => [['a' => true], new Inner(['a'])],
            'multiple' => [['a' => false, 'b' => false, 'c' => true], new Inner(['a', 'b', 'c'])],
        ];
    }

    /**
     * @dataProvider providerIsLast
     * @covers       Inner::isLast
     *
     * @param array $expected
     * @param Inner $it
     */
    public function testIsLastForEach(array $expected, Inner $it): void
    {
        $result = [];
        foreach ($it as $value) {
            $result[$value] = $it->isLast();
        }
        assert\equals($expected, $result);
    }

    /**
     * @dataProvider providerIsLast
     * @covers       Inner::isLast
     *
     * @param array $expected
     * @param Inner $it
     */
    public function testIsLastTraverse(array $expected, Inner $it): void
    {
        $result = fn\traverse($it, function($value, &$key) use(&$it) {
            $key = $value;
            return $it->isLast();
        });
        assert\equals($expected, $result);
    }

    /**
     * @dataProvider providerIsLast
     * @covers       Inner::isLast
     *
     * @param array $expected
     * @param Inner $it
     */
    public function testIsLastTreeDoMap(array $expected, Inner $it): void
    {
        $result = iterator_to_array(new Tree($it, function($value, &$key) use(&$it) {
            $key = $value;
            return $it->isLast();
        }));
        assert\equals($expected, $result);
    }
}
