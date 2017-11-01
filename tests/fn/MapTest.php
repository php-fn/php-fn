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
 * @covers Map
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Map::getIterator
     */
    public function testGetIterator()
    {
        assert\type(Traversable::class, (new Map)->getIterator());
    }

    /**
     * @covers Map::count
     */
    public function testCount()
    {
        assert\equals(0, count(new Map));
        assert\equals(1, count(new Map([null])));
        assert\equals(2, count(new Map([1, 2, 3, 4], function($value) {
            return ($value % 2) ? $value : null;
        })));
    }

    /**
     * @covers Map::map
     */
    public function testMap()
    {
        $duplicate = function($value) {
            return "$value$value";
        };
        $map = (new Map(['a-']))->map($duplicate, $duplicate, $duplicate);
        assert\type(Map::class, $map);
        assert\equals(['a-a-a-a-a-a-a-a-'], map($map));
        assert\equals(['a-a-a-a-a-a-a-a-'], map($map->map()));
    }

    /**
     * @covers Map::keys
     */
    public function testKeys()
    {
        $map = new Map(['a' => null, 'b' => null, 'c' => null]);
        assert\type(Map::class, $map->keys());
        assert\equals(['a', 'b', 'c'], map($map->keys()));
        assert\equals(['A', 'B', 'C'], map($map->keys(function($value) {
            return strtoupper($value);
        })));
    }

    /**
     * @covers Map::merge
     */
    public function testMerge()
    {
        $map = (new Map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c']))->merge(
            ['d' => 'd', 'a' => 'A'],
            new Map(['c' => 'C']),
            ['z']
        );
        assert\type(Map::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd', 'z'], map($map));
    }

    /**
     * @covers Map::replace
     */
    public function testReplace()
    {
        $map = (new Map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c']))->replace(
            ['d' => 'd', 'a' => 'A'],
            new Map(['c' => 'C']),
            ['z']
        );
        assert\type(Map::class, $map);
        assert\equals(['z', 'a' => 'A', 'b' => 'b', 'c' => 'C', 'd' => 'd'], map($map));
    }

    /**
     * @covers Map::diff
     */
    public function testDiff()
    {
        $map = (new Map(['z', 'a' => 'a', 'b' => 'b', 'c' => 'c']))->diff(
            ['d' => 'd', 'a' => 'A'],
            new Map(['c' => 'C']),
            ['z', 'b']
        );
        assert\type(Map::class, $map);
        assert\equals(['a' => 'a', 'c' => 'c'], map($map));
    }
}
