<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

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
        $fn = $this->map(['a' => 'A']);
        assert\true(isset($fn['a']));
        assert\equals('A', $fn['a']);
        assert\false(isset($fn['b']));
        $fn['b'] = 'B';
        assert\true(isset($fn['b']));
        assert\equals('B', $fn['b']);
        unset($fn['a']);
        assert\false(isset($fn['a']));
        assert\exception(new \InvalidArgumentException('a'), function() use($fn) {
           $fn['a'];
        });
        assert\same(['b' => 'B', 'c' => 'C'], traverse($fn->replace(['c' => 'C'])));
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
     * @covers Map::keys
     */
    public function testKeys()
    {
        $map = $this->map(['a' => null, 'b' => null, 'c' => null]);
        assert\type(Map::class, $map->keys());
        assert\equals(['a', 'b', 'c'], traverse($map->keys()));
        assert\equals(['A', 'B', 'C'], traverse($map->keys(function($value) {
            return strtoupper($value);
        })));
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
