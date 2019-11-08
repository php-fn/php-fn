<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Php\Map\Sort;
use Php\test\assert;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Traversable;

class MapTest extends TestCase
{
    protected function map(...$arguments): Map
    {
        return new Map(...$arguments);
    }

    public function testGetIterator(): void
    {
        assert\type(Traversable::class, $this->map()->getIterator());
    }

    public function testCount(): void
    {
        assert\equals(0, count($this->map()));
        assert\equals(1, count($this->map([null])));
        assert\equals(2, count($this->map([1, 2, 3, 4], static function ($value) {
            return ($value % 2) ? $value : null;
        })));
    }

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
        assert\exception(new RuntimeException('a'), static function () use($map) {
           $map['a'];
        });
    }

    public function testThen(): void
    {
        $duplicate = static function ($value) {
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
        assert\equals(['a-a-a-a-a-a-a-a-'], Php::traverse($map));
        assert\equals(['a-a-a-a-a-a-a-a-'], Php::traverse($map->then()));
    }

    public function providerSort(): array
    {
        $map = ['C' => 'C', 'A' => 'a', 'b' => 'B'];
        $compare = static function ($left, $right) {
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
        assert\same($expected, Php::traverse($result));
    }

    public function testKeys(): void
    {
        $map = $this->map(['a' => null, 'b' => null, 'c' => null, 10 => null]);
        assert\type(Map::class, $map->keys());
        assert\same(['a', 'b', 'c', 10], Php::traverse($map->keys()));
        assert\same(['A', 'B', 'C', '10'], Php::traverse($map->keys(static function ($value) {
            return strtoupper($value);
        })));
    }

    public function testValues(): void
    {
        $map = $this->map(['a' => 'A', 'b' => 'B', 'c' => 'C']);
        assert\type(Map::class, $map->values());
        assert\same(['A', 'B', 'C'], Php::traverse($map->values()));

        $increment = static function ($value) {return ++$value;};
        assert\same(['D', 'E', 'F'], Php::traverse($map->values($increment, $increment, $increment)));
    }

    public function testMerge(): void
    {
        $map = $this->map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->merge(
            ['d' => 'd', 'a' => 'A'],
            $this->map(['c' => 'C']),
            ['z']
        );
        assert\type(Map::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd', 'z'], Php::traverse($map));
    }

    public function testProperties(): void
    {
        $data = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $map = $this->map($data);

        assert\same\trial(new RuntimeException('unknown'), static function () use ($map) {
            $map->unknown;
        });

        assert\same\trial(new RuntimeException('values'), static function () use ($map) {
            $property = 'values';
            $map->$property = null;
        });
        assert\same\trial(new RuntimeException('keys'), static function () use ($map) {
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

    public function testTree(): void
    {
        $map = $this->map(['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c']]);
        assert\type(Map::class, $map->tree());
        assert\not\same($map, $map->tree());

        assert\same(
            ['k0' => 'a', 'k1' => ['k2' => 'b', 'k3' => 'c'], 'k2' => 'b', 'k3' => 'c'],
            Php::traverse($map->tree())
        );
        assert\same(
            ['k0' => ['a', 0], 'k1' => [['k2' => 'b', 'k3' => 'c'], 0], 'k3' => ['c', 1]],
            Php::traverse($map->tree(static function ($value, $key, Map\Path $it) {
                return $value === 'b' ? null : Php::mapValue([$value, $it->getDepth()]);
            }))
        );
        assert\same(
            ['k0' => ['a', 0], 'k3' => ['c', 1]],
            Php::traverse($map->leaves(static function ($value, Map\Path $it) {
                return $value === 'b' ? null : Php::mapValue([$value, $it->getDepth()]);
            }))
        );

        $mapper = static function ($value) {
            return Php::mapChildren(['k2' => 'b', 'k3' => 'c'])->andValue(strtoupper($value));
        };

        $map = $this->map(['a'], $mapper);
        assert\same(['A'], $map->values);
        assert\same(['A', 'b', 'c'], $map->tree);
        assert\same(['b', 'c'], $map->leaves);

        assert\same(['A', 'b', 'c'], $this->map(['a'], $mapper)->tree);
        assert\same(['b', 'c'], $this->map(['a'], $mapper)->leaves);

        $map = $this->map(['a'], $mapper);
        assert\same(['A'], Php::traverse($map->values()));
        assert\same(
            ['A' => 0, 'b' => 1, 'c' => 1],
            Php::traverse($map->tree(static function (Map\Path $it, $value) {
                return Php::mapKey($value)->andValue($it->getDepth());
            }))
        );
        assert\same(
            ['b' => 1, 'c' => 1],
            Php::traverse($map->leaves(static function ($value, $key, Map\Path $it) {
                return Php::mapKey($value)->andValue($it->getDepth());
            }))
        );

        $map = $this->map(['k1' => 'a', 'k2' => $this->map(['k3' => 'b', 'k4' => $this->map(['k5' => 'c'])])]);
        assert\same(
            ['k1' => 'a', 'k3' => 'b', 'k5' => 'c'],
            Php::traverse($map->leaves())
        );
        assert\same(
            [0, 0, 1, 1, 2],
            Php::traverse($map->tree(static function (Map\Path $it) {
                static $count = 0;
                return Php::mapValue($it->getDepth())->andKey($count++);
            }))
        );
    }

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
        assert\same($expected, $map->string(static function ($counter, $depth) {
            return ($counter ? PHP_EOL : null) . $counter . '. ' . str_repeat('-', $depth);
        }));

        assert\same("aRb\nR", $this->map(['a{0}b', '{0}'])->string(['R']));
        assert\same('aR1b | R2', $this->map(['a%sb', '%s'])->string(' | ', 'R1', 'R2'));
    }

    public function testEvery(): void
    {
        assert\true($this->map()->every);
        assert\false($this->map([''])->every);
        assert\false($this->map([0])->every);
        assert\false($this->map([false])->every);
        assert\false($this->map([[]])->every);
        assert\false($this->map([null])->every);

        $odd = static function ($value) {
            return $value % 2;
        };

        assert\true($this->map()->every($odd));
        assert\true($this->map([1, 3, 5])->every($odd));
        assert\same('true', $this->map([1, 3, 5])->every($odd, 'true'));
        assert\same(3, $this->map([1, 3, 5])->every($odd, static function (Map $map) {
            return $map->count();
        }));

        assert\false($this->map([1, 3, 5, 2])->every($odd));
        assert\same('false', $this->map([1, 3, 5, 2])->every($odd, null, 'false'));
        assert\same('1352', $this->map([1, 3, 5, 2])->every($odd, null, static function (Map $map) {
            return $map->string('');
        }));
    }

    public function testSome(): void
    {
        assert\false($this->map()->some);
        assert\false($this->map([''])->some);
        assert\false($this->map([0])->some);
        assert\false($this->map([false])->some);
        assert\false($this->map([[]])->some);
        assert\false($this->map([null])->some);
        assert\true($this->map([' '])->some);

        $odd = static function ($value) {
            return $value % 2;
        };

        assert\false($this->map()->some($odd));
        assert\true($this->map([1, 3, 2])->some($odd));
        assert\same('true', $this->map([1, 3, 2])->some($odd, 'true'));
        assert\same(3, $this->map([1, 3, 2])->some($odd, static function (Map $map) {
            return $map->count();
        }));

        assert\false($this->map([2, 4, 6])->some($odd));
        assert\same('false', $this->map([2, 4, 6])->some($odd, null, 'false'));
        assert\same('246', $this->map([2, 4, 6])->some($odd, null, static function (Map $map) {
            return $map->string('');
        }));
    }

    public function testIsLast(): void
    {
        assert\same(
            ['a' => false, 'b' => false, 'c' => true],
            Php::traverse($map = new Map(['a', 'b', 'c'], static function ($value) use (&$map) {
                /** @var Map $map */
                return Php::mapKey($value)->andValue($map->isLast());
            }))
        );
    }

    public function testLimit(): void
    {
        $map = $this->map(['a', 'b', 'c', 'd'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        });

        assert\type(Map::class, $map->limit(0));
        assert\same($all = ['A', 2 => 'C', 'D'], Php::traverse($map->limit(0)));
        assert\same($all, Php::traverse($map->limit(-1)));
        assert\same($all, Php::traverse($map->limit(0, -1)));
        assert\same($all, Php::traverse($map->limit(3)));

        assert\same(['A'], Php::traverse($map->limit(1)));
        assert\same([2 => 'C'], Php::traverse($map->limit(1, 1)));
        assert\same([3 => 'D'], Php::traverse($map->limit(1, 2)));
        assert\same([], Php::traverse($map->limit(1, 3)));

        assert\same(['A', 2 => 'C'], Php::traverse($map->limit(2)));
        assert\same([2 => 'C', 'D'], Php::traverse($map->limit(2, 1)));
        assert\same([3 => 'D'], Php::traverse($map->limit(2, 2)));
    }

    public function testGroupByEmptyString(): void
    {
        assert\equals([
            '' => ['a', 'b']
        ], Php::traverse(['a', 'b'], static function ($value) {
            return Php::mapGroup('')->andValue($value);
        }));

        assert\equals([
            '' => ['a', 'b']
        ], $this->map(['a', 'b'], static function ($value) {
            return Php::mapGroup('')->andValue($value);
        })->traverse);
    }
}
