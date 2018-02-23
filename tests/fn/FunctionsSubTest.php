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
 * @covers sub()
 */
class FunctionsSubTest extends \PHPUnit_Framework_TestCase
{
    /**
     * for iterable:
     *  sub($iterable, $start)
     *  sub($iterable, $start, $callable = null)
     *  sub($iterable, $start, $length, $callable = null)
     *
     * @return array
     */
    public function providerSubWithIterable()
    {
        $candidate = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'];
        $case = function ($expected, $start, $lengthOrCallable = null, $encodingOrCallable = null) use ($candidate) {
            return [
                'expected' => $expected,
                'candidate' => $candidate,
                'start' => $start,
                'lengthOrCallable' => $lengthOrCallable,
                'encodingOrCallable' => $encodingOrCallable,
            ];
        };

        return [
            '1 arg: start = 0' => $case($candidate, 0),
            '1 arg: start > 0' => $case(['c' => 'C', 'd' => 'D', 'e' => 'E'], 2),
            '1 arg: start < 0' => $case(['d' => 'D', 'e' => 'E'], -2),
            '2 args: with callable' => $case(['e' => 'EE'], -1, function ($value) {
                return $value . $value;
            }),
            '2 args: start = 0, length = 0' => $case([], 0, 0),
            '2 args: start = 0, length > 0' => $case(['a' => 'A', 'b' => 'B'], 0, 2),
            '2 args: start = 0, length < 0' => $case(['a' => 'A', 'b' => 'B', 'c' => 'C'], 0, -2),
            '2 args: start > 0, length = 0' => $case([], 2, 0),
            '2 args: start > 0, length > 0' => $case(['c' => 'C', 'd' => 'D'], 2, 2),
            '2 args: start > 0, length < 0' => $case(['c' => 'C'], 2, -2),
            '2 args: start < 0, length = 0' => $case([], -3, 0),
            '2 args: start < 0, length > 0' => $case(['b' => 'B', 'c' => 'C'], -4, 2),
            '2 args: start < 0, length < 0' => $case(['c' => 'C'], -3, -2),
            '3 args: with callable' => $case(['a' => 'AA', 'b' => 'BB'], 0, 2, function ($value) {
                return $value . $value;
            }),
        ];
    }

    /**
     * @dataProvider providerSubWithIterable
     *
     * @covers       sub()
     * @covers       Map::sub()
     *
     * @param array $expected
     * @param array $candidate
     * @param int $start
     * @param int|callable $lengthOrCallable
     * @param callable $encodingOrCallable
     */
    public function testSubWithIterable($expected, $candidate, $start, $lengthOrCallable, $encodingOrCallable)
    {
        assert\same($expected, sub($candidate, $start, $lengthOrCallable, $encodingOrCallable), 'candidate as array');
        assert\same(
            $expected,
            sub(new \ArrayObject($candidate), $start, $lengthOrCallable, $encodingOrCallable),
            'candidate as iterator'
        );
        if (!$encodingOrCallable) {
            assert\same($expected, map($candidate)->sub($start, $lengthOrCallable)->map);
        }
        assert\same([], sub([], $start, $lengthOrCallable, $encodingOrCallable), 'empty candidate');
    }

    /**
     * for string:
     *  sub($string, $start)
     *  sub($string, $start, $callable = null)
     *  sub($string, $start, $length, $callable = null)
     *  sub($string, $start, $length, $encoding, $callable = null)
     *
     * @return array
     */
    public function providerSubWithString()
    {
        $case = function (
            $expected,
            $start,
            $lengthOrCallable = null,
            $encodingOrCallable = null,
            $callableOrNull = null,
            $candidate = 'абвгд'
        ) {
            return [
                'expected' => $expected,
                'candidate' => $candidate,
                'start' => $start,
                'lengthOrCallable' => $lengthOrCallable,
                'encodingOrCallable' => $encodingOrCallable,
                'callableOrNull' => $callableOrNull,
            ];
        };

        $micro = chr(0xB5);

        return [
            '1 arg: start = 0' => $case('абвгд', 0),
            '1 arg: start > 0' => $case('вгд', 2),
            '1 arg: start < 0' => $case('гд', -2),
            '2 args: with callable' => $case('дд', -1, function ($value) {
                return $value . $value;
            }),
            '2 args: start = 0, length = 0' => $case('', 0, 0),
            '2 args: start = 0, length > 0' => $case('аб', 0, 2),
            '2 args: start = 0, length < 0' => $case('абв', 0, -2),
            '2 args: start > 0, length = 0' => $case('', 2, 0),
            '2 args: start > 0, length > 0' => $case('вг', 2, 2),
            '2 args: start > 0, length < 0' => $case('в', 2, -2),
            '2 args: start < 0, length = 0' => $case('', -3, 0),
            '2 args: start < 0, length > 0' => $case('бв', -4, 2),
            '2 args: start < 0, length < 0' => $case('в', -3, -2),
            '3 args: with callable' => $case('абаб', 0, 2, function ($value) {
                return $value . $value;
            }),
            '3 args: with encoding' => $case('&micro;', 0, 1, 'HTML-ENTITIES', null, $micro),
            '4 args' => $case('&MICRO;', 0, 1, 'HTML-ENTITIES', function ($value) {
                return strtoupper($value);
            }, $micro),
        ];
    }

    /**
     * @dataProvider providerSubWithString
     *
     * @covers       ::sub
     *
     * @param array $expected
     * @param array $candidate
     * @param int $start
     * @param int|callable $lengthOrCallable
     * @param callable $encodingOrCallable
     * @param callable $callableOrNull
     */
    public function testSubWithString(
        $expected,
        $candidate,
        $start,
        $lengthOrCallable,
        $encodingOrCallable,
        $callableOrNull
    ) {
        assert\same($expected, sub($candidate, $start, $lengthOrCallable, $encodingOrCallable, $callableOrNull));
        assert\same('', sub('', $start, $lengthOrCallable, $encodingOrCallable, $callableOrNull));
    }
}
