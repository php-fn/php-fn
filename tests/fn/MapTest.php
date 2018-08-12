<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use fn\Map\Sort;
use fn\test\assert;
use LogicException;
use RecursiveIteratorIterator;
use Traversable;

/**
 * @covers Map
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $arguments
     * @return Map
     */
    protected function map(...$arguments)
    {
        return new Map(...$arguments);
    }

    /**
     * @covers Map::getIterator
     */
    public function testGetIterator()
    {
        assert\type(Traversable::class, $this->map()->getIterator());
    }

    /**
     * @covers Map::count
     */
    public function testCount()
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
    public function testArrayAccess()
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
     * @covers Map::map
     */
    public function testMap()
    {
        $duplicate = function($value) {
            return "$value$value";
        };
        $map = $this->map(['a-'])->map($duplicate, $duplicate, $duplicate);
        assert\type(Map::class, $map);
        assert\equals(['a-a-a-a-a-a-a-a-'], traverse($map));
        assert\equals(['a-a-a-a-a-a-a-a-'], traverse($map->map()));
    }

    /**
     * @return array[]
     */
    public function providerSort()
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
    public function testSort(array $expected, array $map, $strategy = null, $flags = null)
    {
        $result = $this->map($map)->sort($strategy, $flags);
        assert\type(Map::class, $result);
        assert\same($expected, traverse($result));
    }

    /**
     * @covers Map::keys
     */
    public function testKeys()
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
    public function testValues()
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
    public function testMerge()
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
    public function testReplace()
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
    public function testDiff()
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
    public function testSub()
    {
        $map = $this->map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->sub(1, -1);
        assert\type(Map::class, $map);
        assert\same(['a' => 'a', 'b' => 'b'], $map->map);
    }

    /**
     * @covers Map::__get
     * @covers Map::__isset
     * @covers Map::__set
     * @covers Map::__unset
     */
    public function testProperties()
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
        assert\true(isset($map->map));
        assert\true(isset($map->values));
        assert\true(isset($map->keys));

        assert\same($data, $map->map);
        assert\same(array_values($data), $map->values);
        assert\same(array_keys($data), $map->keys);

        $map['a'] = '-';
        unset($map['b']);
        $map['d'] = 'D';

        $expected = ['a' => '-', 'c' => 'C', 'd' => 'D'];
        assert\same($expected, $map->map);
        assert\same(array_values($expected), $map->values);
        assert\same(array_keys($expected), $map->keys);
    }

    /**
     * @covers Map::has
     */
    public function testHas()
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
    public function testSearch()
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
    public function testTree()
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
            traverse($map->tree(function($value, $key, RecursiveIteratorIterator $it) {
                return $value === 'b' ? null : mapValue([$value, $it->getDepth()]);
            }))
        );
        assert\same(
            ['k0' => ['a', 0], 'k3' => ['c', 1]],
            traverse($map->leaves(function($value, RecursiveIteratorIterator $it) {
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
            traverse($map->tree(function(RecursiveIteratorIterator $it, $value) {
                return mapKey($value)->andValue($it->getDepth());
            }))
        );
        assert\same(
            ['b' => 1, 'c' => 1],
            traverse($map->leaves(function($value, $key, RecursiveIteratorIterator $it) {
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
            traverse($map->tree(function(RecursiveIteratorIterator $it) {
                static $count = 0;
                return mapValue($it->getDepth())->andKey($count++);
            }))
        );
    }

    /**
     * @covers Map::string
     * @covers Map::__toString
     */
    public function testString()
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
        assert\same("aR1b | R2", $this->map(['a%sb', '%s'])->string(' | ', 'R1', 'R2'));
    }
}
