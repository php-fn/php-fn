<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use ArrayIterator;
use Closure;
use Exception;
use IteratorAggregate;
use Php;
use Php\test\assert;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleXMLElement;

class LazyTest extends TestCase
{
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
     *
     * @param array $expected
     * @param Lazy $it
     */
    public function testIsLastTraverse(array $expected, Lazy $it): void
    {
        $result = Php::traverse($it, static function ($value, &$key) use (&$it) {
            $key = $value;
            return $it->isLast();
        });
        assert\equals($expected, $result);
    }

    /**
     * @dataProvider providerIsLast
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
