<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\_;

use fn\test\assert;

/**
 * @covers \fn\_\
 */
class functionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::toIterable
     */
    public function testToIterable()
    {
        $ar = [true];
        $it = new \ArrayObject($ar);
        assert\same($ar, toIterable([true]));
        assert\same($it, toIterable($it));
        assert\equals($it, toIterable(new \ArrayObject($ar)));
        assert\not\same($it, toIterable(new \ArrayObject($ar)));
        assert\same(['string'], toIterable('string', true));
        assert\same([], toIterable(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toIterable('string');
        });
    }

    /**
     * @covers ::toMap
     */
    public function testToMap()
    {
        assert\equals(['key' => 'value'], toMap(['key' => 'value']));
        assert\equals(['key' => 'value'], toMap(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toMap(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toMap(null);
        });
    }

    /**
     * @covers ::toValues
     */
    public function testToValues()
    {
        assert\equals(['value'], toValues(['key' => 'value']));
        assert\equals(['value'], toValues(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toValues(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toValues(null);
        });
    }
}
