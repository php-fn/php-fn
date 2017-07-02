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
 * @covers \fn
 */
class functionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \fn\skip()
     */
    public function testSkip()
    {
        assert\same(skip(), skip());
        assert\same(skip(), skip(false));
        assert\not\same(skip(true), skip());
        assert\same(skip(true), skip(true));
    }

    /**
     * @covers \fn\map()
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

        assert\same([1 => 'b', 3 => 'd'], map(['a', 'b', 'c', 'd', 'e', 'f'], function ($value) {
            if ($value == 'e') {
                return skip(true);
            }
            if (in_array($value, ['a', 'c'])) {
                return skip();
            }
            return $value;
        }));

        assert\same([1], map('value', 'count', false));
        assert\same(['VALUE'], map('value', $this, false));
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