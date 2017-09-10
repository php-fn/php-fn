<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use fn\test\assert;

/**
 * @covers map\*
 */
class functionsMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers map()
     */
    public function testMap()
    {
        $emptyCallable = function () {
        };
        $message = 'Argument $candidate must be iterable';

        assert\same(['key' => 'value'], map(['key' => 'value']));
        assert\same(['key' => 'value'], map(new \ArrayObject(['key' => 'value'])));
        assert\same([], map(null, false));
        assert\same([], map(null, $emptyCallable, false));

        assert\exception($message, function () {
            map(null);
        });
        assert\exception($message, function () {
            map(null, true);
        });
        assert\exception($message, function ($emptyCallable) {
            map(null, $emptyCallable);
        }, $emptyCallable);
        assert\exception($message, function ($emptyCallable) {
            map(null, $emptyCallable, true);
        }, $emptyCallable);

        assert\same(['v1' => 'k1', 'v2' => 'k2'], map(['k1' => 'v1', 'k2' => 'v2'], function ($value, &$key) {
            $tmp = $key;
            $key = $value;
            return $tmp;
        }));

        assert\same([1 => null, 3 => 'd'], map(['a', 'b', 'c', 'd', 'e', 'f'], function ($value) {
            if ($value === 'e') {
                return map\stop();
            }
            if (in_array($value, ['a', 'c'], true)) {
                return null;
            }
            return $value === 'b' ? map\null() : $value;
        }));

        assert\same([1], map('value', 'count', false));
        assert\same(['VALUE'], map('value', $this, false));

        assert\same(
            ['VALUE', 'KEY' => 'key', 'pair' => 'flip', 'no' => 'changes'],
            map(['value', 'key', 'flip' => 'pair', 'no' => 'changes'], function($value, $key) {
                if ($value === 'value') {
                    return map\value('VALUE');
                }
                if ($value === 'key') {
                    return map\key('KEY');
                }
                if ($key === 'flip') {
                    return map\value($key)->andKey($value);
                }
                return map\value();
            }
        ));
    }

    /**
     * @covers map\null()
     * @covers map\stop()
     */
    public function testNullStop()
    {
        assert\equals(new Map\Value, map\null());
        assert\same(map\null(), map\null());

        assert\equals(new Map\Value, map\stop());
        assert\same(map\stop(), map\stop());

        assert\equals(map\stop(), map\null());
        assert\not\same(map\stop(), map\null());
    }

    /**
     * @covers map\value()
     * @covers map\key()
     * @covers map\children()
     */
    public function testValueKeyChildren()
    {
        assert\equals(new Map\Value, map\value());
        assert\equals(new Map\Value('v'), map\value('v'));
        assert\equals(new Map\Value('v', 'k'), map\value('v', 'k'));
        assert\equals(new Map\Value('v', 'k', 'c'), map\value('v', 'k', 'c'));
        assert\equals((new Map\Value)->andKey('k'), map\key('k'));
        assert\equals((new Map\Value)->andChildren('c'), map\children('c'));
    }

    /**
     * @param mixed $value
     * @param mixed $key
     * @return string
     */
    public function __invoke($value, &$key)
    {
        $key = strtolower($key);
        return strtoupper($value);
    }
}