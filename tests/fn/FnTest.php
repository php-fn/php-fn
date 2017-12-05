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
 * @covers Fn
 */
class FnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $arguments
     * @return Fn
     */
    protected function fn(...$arguments)
    {
        return new Fn(...$arguments);
    }

    /**
     * @covers Fn::getIterator
     */
    public function testGetIterator()
    {
        assert\type(Traversable::class, $this->fn()->getIterator());
    }

    /**
     * @covers Fn::count
     */
    public function testCount()
    {
        assert\equals(0, count($this->fn()));
        assert\equals(1, count($this->fn([null])));
        assert\equals(2, count($this->fn([1, 2, 3, 4], function($value) {
            return ($value % 2) ? $value : null;
        })));
    }

    /**
     * @covers Fn::offsetExists
     * @covers Fn::offsetGet
     * @covers Fn::offsetSet
     * @covers Fn::offsetUnset
     */
    public function testArrayAccess()
    {
        $fn = $this->fn(['a' => 'A']);
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
        assert\same(['b' => 'B', 'c' => 'C'], map($fn->replace(['c' => 'C'])));
    }

    /**
     * @covers Fn::map
     */
    public function testMap()
    {
        $duplicate = function($value) {
            return "$value$value";
        };
        $map = $this->fn(['a-'])->map($duplicate, $duplicate, $duplicate);
        assert\type(Fn::class, $map);
        assert\equals(['a-a-a-a-a-a-a-a-'], map($map));
        assert\equals(['a-a-a-a-a-a-a-a-'], map($map->map()));
    }

    /**
     * @covers Fn::keys
     */
    public function testKeys()
    {
        $map = $this->fn(['a' => null, 'b' => null, 'c' => null]);
        assert\type(Fn::class, $map->keys());
        assert\equals(['a', 'b', 'c'], map($map->keys()));
        assert\equals(['A', 'B', 'C'], map($map->keys(function($value) {
            return strtoupper($value);
        })));
    }

    /**
     * @covers Fn::merge
     */
    public function testMerge()
    {
        $map = $this->fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->merge(
            ['d' => 'd', 'a' => 'A'],
            $this->fn(['c' => 'C']),
            ['z']
        );
        assert\type(Fn::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd', 'z'], map($map));
    }

    /**
     * @covers Fn::replace
     */
    public function testReplace()
    {
        $map = $this->fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->replace(
            ['d' => 'd', 'a' => 'A'],
            $this->fn(['c' => 'C']),
            ['z']
        );
        assert\type(Fn::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd'], map($map));
    }

    /**
     * @covers Fn::diff
     */
    public function testDiff()
    {
        $map = $this->fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->diff(
            ['d' => 'd', 'a' => 'A'],
            $this->fn(['c' => 'C']),
            ['z', 'b']
        );
        assert\type(Fn::class, $map);
        assert\equals(['a' => 'a', 'c' => 'c'], map($map));
    }

    /**
     * @covers Fn::sub
     */
    public function testSub()
    {
        $map = $this->fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c'])->sub(1, -1);
        assert\type(Fn::class, $map);
        assert\same(['a' => 'a', 'b' => 'b'], $map->map);
    }

    /**
     * @covers Fn::__get
     * @covers Fn::__isset
     * @covers Fn::__set
     * @covers Fn::__unset
     */
    public function testProperties()
    {
        $data = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $map = $this->fn($data);

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
     * @covers Fn::has
     */
    public function testHas()
    {
        $map = $this->fn(['a', '1']);
        assert\true($map->has('a'));
        assert\true($map->has('1'));
        assert\false($map->has(1));
        assert\true($map->has(1, false));
        assert\false($map->has('A'));
        assert\false($map->has('A', false));
    }

    /**
     * @covers Fn::search
     */
    public function testSearch()
    {
        $map = $this->fn(['a', '1']);
        assert\same(0, $map->search('a'));
        assert\same(1, $map->search('1'));
        assert\same(false, $map->search(1));
        assert\same(1, $map->search(1, false));
        assert\same(false, $map->search('A'));
        assert\same(false, $map->search('A', false));
    }
}
