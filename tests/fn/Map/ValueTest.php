<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use fn\test\assert;
use fn;

/**
 * @covers Value
 */
class ValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Value::__construct
     * @covers Value::__isset
     * @covers Value::__get
     * @covers Value::andValue
     * @covers Value::andKey
     * @covers Value::andGroup
     * @covers Value::andChildren
     */
    public function testProperties()
    {
        $val = new Value;
        assert\equals(
            [false, false, false, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andValue(null));
        assert\equals(
            [true, false, false, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andKey(null));
        assert\equals(
            [true, true, false, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andGroup(null));
        assert\equals(
            [true, true, true, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andChildren(null));
        assert\equals(
            [true, true, true, true],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value('v');
        assert\equals(
            [true, false, false, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals(['v', null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value(null, 'k');
        assert\equals(
            [true, true, false, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, 'k', null, null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value(null, null, 'g');
        assert\equals(
            [true, true, true, false],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, 'g', null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value(null, null, null, 'c');
        assert\equals(
            [true, true, true, true],
            [isset($val->value), isset($val->key), isset($val->group), isset($val->children)]
        );
        assert\equals([null, null, null, 'c'], [$val->value, $val->key, $val->group, $val->children]);
    }

    /**
     * @covers Value::__set
     * @covers Value::__unset
     * @covers Value::__get
     * @covers Value::__isset
     */
    public function testExceptions()
    {
        fn\traverse(['value', 'key', 'children', 'unknown'], function($property) {
            assert\exception(new \InvalidArgumentException($property), function($property) {
                $val = new Value;
                $val->$property = null;
            }, $property);

            assert\exception(new \InvalidArgumentException($property), function($property) {
                $val = new Value;
                unset($val->$property);
            }, $property);
        });

        $val = new Value;
        assert\false(isset($val->unknown));
        assert\exception(new \InvalidArgumentException('unknown'), function($val) {
            $val->unknown;
        }, $val);
    }
}
