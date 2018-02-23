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
     * @covers ::toTraversable
     */
    public function testToTraversable()
    {
        $ar = [true];
        $it = new \ArrayObject($ar);
        assert\same($ar, toTraversable([true]));
        assert\same($it, toTraversable($it));
        assert\equals($it, toTraversable(new \ArrayObject($ar)));
        assert\not\same($it, toTraversable(new \ArrayObject($ar)));
        assert\same(['string'], toTraversable('string', true));
        assert\same([], toTraversable(null, true));
        assert\exception('argument $iterable must be iterable', function () {
            toTraversable('string');
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
