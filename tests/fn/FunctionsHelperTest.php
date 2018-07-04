<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use function fn\_\toArray;
use function fn\_\toString;
use function fn\_\toTraversable;
use function fn\_\toValues;
use fn\test\assert;

/**
 * @covers \fn\_\
 */
class FunctionsHelperTest extends \PHPUnit_Framework_TestCase
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
        assert\exception('argument $candidate must be traversable', function () {
            toTraversable('string');
        });
    }

    /**
     * @covers ::toArray
     */
    public function testToArray()
    {
        assert\equals(['key' => 'value'], toArray(['key' => 'value']));
        assert\equals(['key' => 'value'], toArray(new \ArrayObject(['key' => 'value'])));
        assert\equals([], toArray(null, true));
        assert\exception('argument $candidate must be traversable', function () {
            toArray(null);
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
        assert\exception('argument $candidate must be traversable', function () {
            toValues(null);
        });
    }

    /**
     * @dataProvider providerToString
     * @covers ::toString
     * @param string $expected
     * @param string $subject
     * @param array $replacements
     */
    public function testToString($expected, $subject, ...$replacements)
    {
        assert\same($expected, toString($subject, ...$replacements));
    }

    /**
     * @return array[]
     */
    public function providerToString()
    {
        return [
            '{0 %s %d  | format' => [
                '{0 string 7 111',
                '{0 %s %d %b',
                'string', 7, 7
            ],
            '{0}{unknown}{merged} %s {1} {orig} {2} | merged replace' => [
                'zero{unknown}RENAMED %s {1} ORIG two',
                '{0}{unknown}{merged} %s {1} {orig} {2}',
                'zero', ['merged' => 'NAMED', 'orig' => 'ORIG'], 'two', ['merged' => 'RENAMED']
            ],
            '{0} | replace without args' => ['{0}', '{0}'],
            '%s | format without args' => ['%s', '%s'],
            'null | with args' => ['', null, 'arg'],
            'null' => ['', null],
        ];
    }
}