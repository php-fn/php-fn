<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\Map\Sort;
use fn\test\assert;
use LogicException;
use Traversable;

/**
 * @covers Map
 */
class MapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $arguments
     * @return Map
     */
    protected function map(...$arguments): Map
    {
        return new Map(...$arguments);
    }

    /**
     * @covers Map::getIterator
     */
    public function testGetIterator(): void
    {
        assert\type(Traversable::class, $this->map()->getIterator());
    }

    /**
     * @covers Map::count
     */
    public function testCount(): void
    {
        assert\equals(0, count($this->map()));
        assert\equals(1, count($this->map([null])));
        assert\equals(2, count($this->map([1, 2, 3, 4], function($value) {
            return ($value % 2) ? $value : null;
        })));
    }

    /**
     * @covers Map::offsetExists
     * @covers Map::offsetGet
     * @covers Map::offsetSet
     * @covers Map::offsetUnset
     */
    public function testArrayAccess(): void
    {
        $map = $this->map(['a' => 'A']);
        assert\true(isset($map['a']));
        assert\equals('A', $map['a']);
        assert\false(isset($map['b']));
        $map['b'] = 'B';
        assert\true(isset($map['b']));
        assert\equals('B', $map['b']);
        unset($map['a']);
        assert\false(isset($map['a']));
        assert\exception(new \InvalidArgumentException('a'), function() use($map) {
           $map['a'];
        });
        assert\same(['b' => 'B', 'c' => 'C'], traverse($map->replace(['c' => 'C'])));
    }

    /**
     * @covers Map::then
     */
    public function testThen(): void
    {
        $duplicate = function($value) {
            return "$value$value";
        };

        $m1 = $this->map(['a-'], $duplicate);
        $m2 = $m1->then($duplicate);
        $m3 = $m2->then($duplicate);
        assert\same(['a-a-'], $m1->traverse);
        assert\same(['a-a-a-a-'], $m2->traverse);
        assert\same(['a-a-a-a-a-a-a-a-'], $m3->traverse);

        $map = $this->map(['a-'])->then($duplicate, $duplicate, $duplicate);
        assert\type(Map::class, $map);
        assert\equals(['a-a-a-a-a-a-a-a-'], traverse($map));
        assert\equals(['a-a-a-a-a-a-a-a-'], traverse($map->then()));
    }

    /**
     * @return array[]
     */
    public function providerSort(): array
    {
        $map = ['C' => 'C', 'A' => 'a', 'b' => 'B'];
        $compare = function($left, $right) {
            static $sort = [
                'B' => 1,
                'a' => 2,
                'C' => 3,
                'b' => 4,
                'A' => 5,
            ];
            return $sort[$left] - $sort[$right];
        };

        return [
            'callback' => [['b' => 'B', 'A' => 'a', 'C' => 'C'], $map, $compare],
            'callback,reverse' => [$map, $map, $compare, Sort::REVERSE],
            'callback,keys' => [['C' => 'C', 'b' => 'B', 'A' => 'a'], $map, $compare, Sort::KEYS],
            'callback,keys|reverse' => [['A' => 'a', 'b' => 'B', 'C' => 'C'], $map, $compare, Sort::KEYS | Sort::REVERSE],
            'empty' => [[], []],
            'not specified' => [['b' => 'B', 'C' => 'C', 'A' => 'a'], $map],
            'regular' => [['b' => 'B', 'C' => 'C', 'A' => 'a'], $map, Sort::REGULAR],
            'reverse' => [['A' => 'a', 'C' => 'C', 'b' => 'B'], $map, Sort::REVERSE],
            'keys' => [['A' => 'a', 'C' => 'C', 'b' => 'B'], $map, Sort::KEYS],
            'keys|reverse' => [['b' => 'B', 'C' => 'C', 'A' => 'a'], $map, Sort::KEYS | Sort::REVERSE],
            'keys,reverse' => [['b' => 'B', 'C' => 'C', 'A' => 'a'], $map, Sort::KEYS, Sort::REVERSE],
            'string|case' => [['A' => 'a', 'b' => 'B', 'C' => 'C'], $map, Sort::STRING | Sort::FLAG_CASE],
            'string|case|keys' => [['A' => 'a', 'b' => 'B', 'C' => 'C'], $map, null, Sort::STRING | Sort::FLAG_CASE | Sort::KEYS],
            'string' => [[2 => 'a10b', 1 => 'a1ba', 0 => 'a2bB'], ['a2bB', 'a1ba', 'a10b'], Sort::STRING],
            'natural' => [[1 => 'a1ba', 0 => 'a2bB', 2 => 'a10b'], ['a2bB', 'a1ba', 'a10b'], Sort::NATURAL],
            'numeric' => [[1 => '1', 0 => '11', 2 => '12'], ['11', '1', '12'], Sort::NUMERIC],
        ];
    }

    /**
     * @dataProvider providerSort
     * @covers \fn\Map::sort
     * @covers \fn\Map\Sort::getIterator
     *
     * @param array $expected
     * @param array $map
     * @param callable|int $strategy
     * @param int $flags
     */
    public function testSort(array $expected, array $map, $strategy = null, $flags = null): void
    {
        $result = $this->map($map)->sort($strategy, $flags);
        assert\type(Map::class, $result);
        assert\same($expected, traverse($result));
    }

    /**
     * @covers Map::keys
     */
    public function testKeys(): void
    {
        $map = $this->map(['a' => null, 'b' => null, 'c' => null, 10 => null]);
        assert\type(Map::class, $map->keys());
        assert\same(['a', 'b', 'c', 10], traverse($map->keys()));
        assert\same(['A', 'B', 'C', '10'], traverse($map->keys(function($value) {
            return strtoupper($value);
        })));
    }

    /**
     * @covers Map::values
     */
    public function testValues(): void
    {
        $map = $this->map(['a' => 'A', 'b' => 'B', 'c' => 'C']);
        assert\type(Map::class, $map->values());
        assert\same(['A', 'B', 'C'], traverse($map->values()));

        $increment = function($value) {return ++$value;};
        assert\same(['D', 'E', 'F'], traverse($map->values($increment, $increment, $increment)));
    }

    /**
     * @covers Map::merge
     */
    public function testMerge(): void
    {
        $map = $this->map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->merge(
            ['d' => 'd', 'a' => 'A'],
            $this->map(['c' => 'C']),
            ['z']
        );
        assert\type(Map::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd', 'z'], traverse($map));
    }

    /**
     * @covers Map::replace
     */
    public function testReplace(): void
    {
        $map = $this->map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->replace(
            ['d' => 'd', 'a' => 'A'],
            $this->map(['c' => 'C']),
            ['z']
        );
        assert\type(Map::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd'], traverse($map));
    }

    /**
     * @covers Map::diff
     */
    public function testDiff(): void
    {
        $map = $this->map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->diff(
            ['d' => 'd', 'a' => 'A'],
            $this->map(['c' => 'C']),
            ['z', 'b']
        );
        assert\type(Map::class, $map);
        assert\equals(['a' => 'a', 'c' => 'c'], traverse($map));
    }

    /**
     * @covers Map::sub
     */
    public function testSub(): void
    {
        $map = $this->map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->sub(1, -1);
        assert\type(Map::class, $map);
        assert\same(['a' => 'a', 'b' => 'b'], $map->traverse);
    }

    /**
     * @covers Map::__get
     * @covers Map::__isset
     * @covers Map::__set
     * @covers Map::__unset
     */
    public function testProperties(): void
    {
        $data = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $map = $this->map($data);

        assert\same\trial(new LogicException('unknown'), function() use($map) {
            $map->unknown;
        });

        assert\same\trial(new LogicException('values'), function() use($map) {
            $property = 'values';
            $map->$property = null;
        });
        assert\same\trial(new LogicException('keys'), function() use($map) {
            $property = 'keys';
            unset($map->$property);
        });

        assert\false(isset($map->unknown));
        assert\true(isset($map->traverse));
        assert\true(isset($map->values));
        assert\true(isset($map->keys));

        assert\same($data, $map->traverse);
        assert\same(array_values($data), $map->values);
        assert\same(array_keys($data), $map->keys);

        $map['a'] = '-';
        unset($map['b']);
        $map['d'] = 'D';

        $expected = ['a' => '-', 'c' => 'C', 'd' => 'D'];
        assert\same($expected, $map->traverse);
        assert\same(array_values($expected), $map->values);
        assert\same(array_keys($expected), $map->keys);
    }

    /**
     * @covers Map::has
     */
    public function testHas(): void
    {
        $map = $this->map(['a', '1']);
        assert\true($map->has('a'));
        assert\true($map->has('1'));
        assert\false($map->has(1));
        assert\true($map->has(1, false));
        assert\false($map->has('A'));
        assert\false($map->has('A', false));
    }

    /**
     * @covers Map::search
     */
    public function testSearch(): void
    {
        $map = $this->map(['a', '1']);
        assert\same(0, $map->search('a'));
        assert\same(1, $map->search('1'));
        assert\same(false, $map->search(1));
        assert\same(1, $map->search(1, false));
        assert\same(false, $map->search('A'));
        assert\same(false, $map->search('A', false));
    }

    /**
     * @covers Map::tree
     * @covers Map::leaves
     * @covers ::recursive
     */
    public function testTree(): void
    {
        $map = $this->map(['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c']]);
        assert\type(Map::class, $map->tree());
        assert\not\same($map, $map->tree());

        assert\same(
            ['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c'], 'k2' => 'b', 'k3' => 'c'],
            traverse($map->tree())
        );
        assert\same(
            ['k0' => ['a', 0], 'k1' => [['k2' => 'b', 'k3' => 'c'], 0], 'k3' => ['c', 1]],
            traverse($map->tree(function($value, $key, Map\Path $it) {
                return $value === 'b' ? null : mapValue([$value, $it->getDepth()]);
            }))
        );
        assert\same(
            ['k0' => ['a', 0], 'k3' => ['c', 1]],
            traverse($map->leaves(function($value, Map\Path $it) {
                return $value === 'b' ? null : mapValue([$value, $it->getDepth()]);
            }))
        );

        $mapper = function($value) {
            return mapChildren(['k2' => 'b', 'k3' => 'c'])->andValue(strtoupper($value));
        };

        $map = $this->map(['a'], $mapper);
        assert\same(['A'], $map->values);
        assert\same(['A', 'b', 'c'], $map->tree);
        assert\same(['b', 'c'], $map->leaves);

        assert\same(['A', 'b', 'c'], $this->map(['a'], $mapper)->tree);
        assert\same(['b', 'c'], $this->map(['a'], $mapper)->leaves);

        $map = $this->map(['a'], $mapper);
        assert\same(['A'], traverse($map->values()));
        assert\same(
            ['A' => 0, 'b' => 1, 'c' => 1],
            traverse($map->tree(function(Map\Path $it, $value) {
                return mapKey($value)->andValue($it->getDepth());
            }))
        );
        assert\same(
            ['b' => 1, 'c' => 1],
            traverse($map->leaves(function($value, $key, Map\Path $it) {
                return mapKey($value)->andValue($it->getDepth());
            }))
        );

        $map = $this->map(['k1' => 'a', 'k2' => $this->map(['k3' => 'b', 'k4' => $this->map(['k5' => 'c'])])]);
        assert\same(
            ['k1' => 'a', 'k3' => 'b', 'k5' => 'c'],
            traverse($map->leaves())
        );
        assert\same(
            [0, 0, 1, 1, 2],
            traverse($map->tree(function(Map\Path $it) {
                static $count = 0;
                return mapValue($it->getDepth())->andKey($count++);
            }))
        );
    }

    /**
     * @covers Map::string
     * @covers Map::__toString
     */
    public function testString(): void
    {
        $map = $this->map([['a'], 'b', [$this->map(['c']), ['d', ['e']]], 'f']);

        assert\same("a\nb\nc\nd\ne\nf", $map->string());
        assert\same("a\nb\nc\nd\ne\nf", $map->string);
        assert\same("a\nb\nc\nd\ne\nf", (string)$map);
        assert\same('a b c d e f', $map->string(' '));
        assert\same('abcdef', $map->string(''));

        $expected = <<<EOF
0. -a
1. b
2. --c
3. --d
4. ---e
5. f
EOF;
        assert\same($expected, $map->string(function($counter, $depth) {
            return ($counter ? PHP_EOL : null) . $counter . '. ' . str_repeat('-', $depth);
        }));

        assert\same("aRb\nR", $this->map(['a{0}b', '{0}'])->string(['R']));
        assert\same('aR1b | R2', $this->map(['a%sb', '%s'])->string(' | ', 'R1', 'R2'));
    }

    /**
     * @covers Map::every
     */
    public function testEvery(): void
    {
        assert\true($this->map()->every);
        assert\false($this->map([''])->every);
        assert\false($this->map([0])->every);
        assert\false($this->map([false])->every);
        assert\false($this->map([[]])->every);
        assert\false($this->map([null])->every);


        $odd = function($value) {
            return $value % 2;
        };

        assert\true($this->map()->every($odd));
        assert\true($this->map([1, 3, 5])->every($odd));
        assert\same('true', $this->map([1, 3, 5])->every($odd, 'true'));
        assert\same(3, $this->map([1, 3, 5])->every($odd, function(Map $map) {
            return $map->count();
        }));

        assert\false($this->map([1, 3, 5, 2])->every($odd));
        assert\same('false', $this->map([1, 3, 5, 2])->every($odd, null, 'false'));
        assert\same('1352', $this->map([1, 3, 5, 2])->every($odd, null, function(Map $map) {
            return $map->string('');
        }));
    }

    /**
     * @covers Map::some
     */
    public function testSome(): void
    {
        assert\false($this->map()->some);
        assert\false($this->map([''])->some);
        assert\false($this->map([0])->some);
        assert\false($this->map([false])->some);
        assert\false($this->map([[]])->some);
        assert\false($this->map([null])->some);
        assert\true($this->map([' '])->some);

        $odd = function($value) {
            return $value % 2;
        };

        assert\false($this->map()->some($odd));
        assert\true($this->map([1, 3, 2])->some($odd));
        assert\same('true', $this->map([1, 3, 2])->some($odd, 'true'));
        assert\same(3, $this->map([1, 3, 2])->some($odd, function(Map $map) {
            return $map->count();
        }));

        assert\false($this->map([2, 4, 6])->some($odd));
        assert\same('false', $this->map([2, 4, 6])->some($odd, null, 'false'));
        assert\same('246', $this->map([2, 4, 6])->some($odd, null, function(Map $map) {
            return $map->string('');
        }));
    }

    /**
     * @covers Map::isLast
     */
    public function testIsLast(): void
    {
        assert\same(
            ['a' => false, 'b' => false, 'c' => true],
            traverse($map = new Map(['a', 'b', 'c'], function($value) use(&$map) {
                /** @var Map $map */
                return mapKey($value)->andValue($map->isLast());
            }))
        );
    }

    /**
     * @covers Map::limit
     */
    public function testLimit(): void
    {
        $map = $this->map(['a', 'b', 'c', 'd'], function($value) {
            return $value === 'b' ? null : strtoupper($value);
        });

        assert\type(Map::class, $map->limit(0));
        assert\same($all = ['A', 2 => 'C', 'D'], traverse($map->limit(0)));
        assert\same($all, traverse($map->limit(-1)));
        assert\same($all, traverse($map->limit(0, -1)));
        assert\same($all, traverse($map->limit(3)));

        assert\same(['A'], traverse($map->limit(1)));
        assert\same([2 => 'C'], traverse($map->limit(1, 1)));
        assert\same([3 => 'D'], traverse($map->limit(1, 2)));
        assert\same([], traverse($map->limit(1, 3)));

        assert\same(['A', 2 => 'C'], traverse($map->limit(2)));
        assert\same([2 => 'C', 'D'], traverse($map->limit(2, 1)));
        assert\same([3 => 'D'], traverse($map->limit(2, 2)));
    }

    /**
     * @covers Tree::doMap
     */
    public function testGroupByEmptyString(): void
    {
        assert\equals([
            '' => ['a', 'b']
        ], traverse(['a', 'b'], static function ($value) {
            return mapGroup('')->andValue($value);
        }));

        assert\equals([
            '' => ['a', 'b']
        ], $this->map(['a', 'b'], static function ($value) {
            return mapGroup('')->andValue($value);
        })->traverse);
    }
}
