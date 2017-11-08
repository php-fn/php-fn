<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use fn\test\assert;
use Traversable;

/**
 * @covers Fn
 */
class FnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Fn::getIterator
     */
    public function testGetIterator()
    {
        assert\type(Traversable::class, (new Fn)->getIterator());
    }

    /**
     * @covers Fn::count
     */
    public function testCount()
    {
        assert\equals(0, count(new Fn));
        assert\equals(1, count(new Fn([null])));
        assert\equals(2, count(new Fn([1, 2, 3, 4], function($value) {
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
        $fn = new Fn(['a' => 'A']);
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
        $map = (new Fn(['a-']))->map($duplicate, $duplicate, $duplicate);
        assert\type(Fn::class, $map);
        assert\equals(['a-a-a-a-a-a-a-a-'], map($map));
        assert\equals(['a-a-a-a-a-a-a-a-'], map($map->map()));
    }

    /**
     * @covers Fn::keys
     */
    public function testKeys()
    {
        $map = new Fn(['a' => null, 'b' => null, 'c' => null]);
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
        $map = (new Fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c']))->merge(
            ['d' => 'd', 'a' => 'A'],
            new Fn(['c' => 'C']),
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
        $map = (new Fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c']))->replace(
            ['d' => 'd', 'a' => 'A'],
            new Fn(['c' => 'C']),
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
        $map = (new Fn(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c']))->diff(
            ['d' => 'd', 'a' => 'A'],
            new Fn(['c' => 'C']),
            ['z', 'b']
        );
        assert\type(Fn::class, $map);
        assert\equals(['a' => 'a', 'c' => 'c'], map($map));
    }
}
