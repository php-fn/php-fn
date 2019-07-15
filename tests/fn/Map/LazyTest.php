<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use ArrayIterator;
use Closure;
use Exception;
use fn;
use fn\test\assert;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleXMLElement;

/**
 * @coversDefaultClass Lazy
 */
class LazyTest extends TestCase
{
    private static function proxy($traversable): IteratorAggregate
    {
        return new class($traversable) implements IteratorAggregate
        {
            private $traversable;

            public function __construct($traversable)
            {
                $this->traversable = $traversable instanceof Closure ? $traversable($this) : $traversable;
            }

            public function getIterator()
            {
                return $this->traversable;
            }
        };
    }

    /**
     * @return array
     */
    public function providerUnify(): array
    {
        return [
            'intern traversable classes are wrapped around IteratorIterator' => [
                'expected' => [],
                'proxy' => self::proxy(new SimpleXMLElement('<root/>')),
            ],
            '$inner::getIterator returns same instance' => [
                'expected' => new RuntimeException('Implementation $proxy::getIterator returns the same instance'),
                'proxy' => self::proxy(static function($that) {return $that;}),
            ],
            '$proxy::getIterator is too deep' => [
                'expected' => new RuntimeException('$proxy::getIterator is too deep'),
                'proxy' => self::proxy(static function() {
                    return self::proxy(static function() {
                        return self::proxy(static function() {
                            return self::proxy(static function() {
                                return self::proxy(static function() {
                                    return self::proxy(static function() {
                                        return self::proxy(static function() {
                                            return self::proxy(static function() {
                                                return self::proxy(static function() {
                                                    return self::proxy(static function() {
                                                        return self::proxy(static function() {
                                                            return self::proxy(static function() {});
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
                })
            ],
            '$inner depth = 3' => [
                'expected' => ['depth' => 3],
                'proxy' => new Lazy(static function() {
                    return new Lazy(static function() {
                        return new Lazy(static function() {
                            return ['depth' => 3];
                        });
                    });
                }),
            ],
            'simple iterator' => [
                'expected' => ['a' => 'a', 'b' => ['c' => 'd']],
                'proxy' => new ArrayIterator(['a' => 'a', 'b' => ['c' => 'd']]),
            ],
            'simple array' => [
                'expected' => ['a', 'b', 'c'],
                'proxy' => ['a', 'b', 'c'],
            ],
            'empty array' => [
                'expected' => [],
                'proxy' => [],
            ],
            'null => exception' => [
                'expected' => new RuntimeException('Property $proxy must be iterable'),
                null
            ],
            '=> EmptyIterator' => [
                'expected' => [],
            ],
        ];
    }

    /**
     * @dataProvider providerUnify
     *
     * @covers \fn\Map\Lazy::unify
     *
     * @param array|Exception $expected
     * @param callable|iterable ...$proxy
     */
    public function testUnify($expected, ...$proxy): void
    {
        assert\equals\trial($expected, static function () use ($proxy) {
            return fn\traverse(new Lazy(...$proxy));
        });
    }

    /**
     * @covers \fn\Map\Lazy::isLast
     */
    public function testIsLastExplicitIteration(): void
    {
        $it = new Lazy([]);
        assert\same(null, $it->isLast());
        $it->rewind();
        assert\same(null, $it->isLast());

        $it = new Lazy(['a', 'b', 'c']);

        assert\true($it->valid());
        assert\same('a', $it->current());
        assert\same(0, $it->key());
        assert\same(false, $it->isLast());
        assert\true($it->valid());
        assert\same('a', $it->current());
        assert\same(0, $it->key());
        assert\same(false, $it->isLast());

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

        $it = new Lazy(['a']);
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
            'empty'    => [[], new Lazy([])],
            'single'   => [['a' => true], new Lazy(['a'])],
            'multiple' => [['a' => false, 'b' => false, 'c' => true], new Lazy(['a', 'b', 'c'])],
        ];
    }

    /**
     * @dataProvider providerIsLast
     * @covers \fn\Map\Lazy::isLast
     *
     * @param array $expected
     * @param Lazy $it
     */
    public function testIsLastForEach(array $expected, Lazy $it): void
    {
        $result = [];
        foreach ($it as $value) {
            $result[$value] = $it->isLast();
        }
        assert\equals($expected, $result);
    }

    /**
     * @dataProvider providerIsLast
     * @covers \fn\Map\Lazy::isLast
     *
     * @param array $expected
     * @param Lazy $it
     */
    public function testIsLastTraverse(array $expected, Lazy $it): void
    {
        $result = fn\traverse($it, static function($value, &$key) use(&$it) {
            $key = $value;
            return $it->isLast();
        });
        assert\equals($expected, $result);
    }

    /**
     * @dataProvider providerIsLast
     * @covers \fn\Map\Lazy::isLast
     *
     * @param array $expected
     * @param Lazy $it
     */
    public function testIsLastTreeDoMap(array $expected, Lazy $it): void
    {
        $result = iterator_to_array(new Tree($it, static function($value, &$key) use(&$it) {
            $key = $value;
            return $it->isLast();
        }));
        assert\equals($expected, $result);
    }
}
