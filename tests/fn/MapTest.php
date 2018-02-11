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
        $map = $this->map(['a' => null, 'b' => null, 'c' => null]);
        assert\type(Map::class, $map->keys());
        assert\same(['a', 'b', 'c'], traverse($map->keys()));
        assert\same(['A', 'B', 'C'], traverse($map->keys(function($value) {
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
}
