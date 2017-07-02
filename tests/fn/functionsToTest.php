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
 * @covers \fn\to
 */
class functionsToTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers to\iterable()
     */
    public function testToIterable()
    {
        $ar = [true];
        $it = new \ArrayObject($ar);
        assert\same($ar, to\iterable([true]));
        assert\same($it, to\iterable($it));
        assert\equals($it, to\iterable(new \ArrayObject($ar)));
        assert\not\same($it, to\iterable(new \ArrayObject($ar)));
        assert\same(['string'], to\iterable('string', false));
        assert\same([], to\iterable(null, false));
        assert\exception('Argument $candidate must be iterable', function () {
            to\iterable('string');
        });
    }

    /**
     * @covers to\map()
     */
    public function testToMap()
    {
        assert\equals(['key' => 'value'], to\map(['key' => 'value']));
        assert\equals(['key' => 'value'], to\map(new \ArrayObject(['key' => 'value'])));
        assert\equals([], to\map(null, false));
        assert\exception('Argument $candidate must be iterable', function() {
           to\map(null);
        });
    }

    /**
     * @covers to\values()
     */
    public function testToValues()
    {
        assert\equals(['value'], to\values(['key' => 'value']));
        assert\equals(['value'], to\values(new \ArrayObject(['key' => 'value'])));
        assert\equals([], to\values(null, false));
        assert\exception('Argument $candidate must be iterable', function() {
            to\values(null);
        });
    }
}