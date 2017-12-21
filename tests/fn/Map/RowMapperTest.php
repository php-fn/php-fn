<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use DomainException;
use fn;
use fn\test\assert;
use OutOfRangeException;

/**
 * @covers RowMapper
 */
class RowMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function providerInvoke()
    {
        $undefined = new OutOfRangeException('undefined index: foo');
        $useKey = function($row, $key, Value $mapped) {
            return $key;
        };

        return [
            'unknown key column' => [$undefined, new RowMapper('foo'), []],
            'map key' => [fn\mapKey('bar'), new RowMapper('foo'), ['foo' => 'bar']],
            'map key closure' => [fn\mapKey('mapped'), new RowMapper($useKey), [], 'mapped'],
            'unknown value scalar' => [$undefined, new RowMapper(null, 'foo'), []],
            'unknown value array' => [$undefined, new RowMapper(null, ['foo']), []],
            'map value scalar' => [fn\mapValue('bar'), new RowMapper(null, 'foo'), ['foo' => 'bar']],
            'map value array' => [
                fn\mapValue(['c1' => 'bar', 'c2' => 'bar', 'foo' => 'bar']),
                new RowMapper(null, ['c1' => 'foo', 'c2' => 'foo', 'foo']),
                ['foo' => 'bar']
            ],
            'map value closure' => [fn\mapValue('mapped'), new RowMapper(null, $useKey), [], 'mapped'],
            'unknown group column' => [$undefined, new RowMapper(null, null, 'foo'), []],
            'map single group' => [
                fn\mapGroup(['bar']),
                new RowMapper(null, null, 'foo'),
                ['foo' => 'bar']
            ],
            'map multiple group' => [
                fn\mapGroup(['bar', 'bar']),
                new RowMapper(null, null, 'foo', 'foo'),
                ['foo' => 'bar']
            ],
            'blank' => [new Value, new RowMapper(null), []],
            'wrong row type' => [
                new DomainException('row should be of type: array|ArrayAccess|iterable'),
                new RowMapper(null),
                null
            ],
        ];
    }

    /**
     * @dataProvider providerInvoke
     *
     * @covers RowMapper::__invoke
     *
     * @param mixed $expected
     * @param RowMapper $mapper
     * @param mixed $row
     * @param mixed $key
     */
    public function testInvoke($expected, RowMapper $mapper, $row, $key = null)
    {
        assert\equals\trial($expected, function(RowMapper $mapper, $row, $key) {
            return $mapper($row, $key);
        }, $mapper, $row, $key);
    }

    /**
     * @covers fn\mapRow()
     */
    public function testFunctionMapRow()
    {
        assert\equals([
            'g1' => ['a' => 'A', 'c' => 'C'],
            'g2' => ['b' => 'B'],
        ], fn\traverse([
            ['id' => 'a', 'name' => 'A', 'group' => 'g1'],
            ['id' => 'b', 'name' => 'B', 'group' => 'g2'],
            ['id' => 'c', 'name' => 'C', 'group' => 'g1'],
        ], fn\mapRow('id', 'name', 'group')));

        assert\equals([
            'g1' => ['a' => fn\mapValue(['k1' => 'A']), 'c' => fn\mapValue(['k3' => 'C'])],
            'g2' => ['b' => fn\mapValue(['k2' => 'B'])],
        ], fn\traverse([
            'k1' => ['id' => 'a', 'name' => 'A', 'group' => 'g1'],
            'k2' => ['id' => 'b', 'name' => 'B', 'group' => 'g2'],
            'k3' => ['id' => 'c', 'name' => 'C', 'group' => 'g1'],
        ], fn\mapRow('id', function(array $row, $key) {
            return fn\mapValue([$key => $row['name']]);
        }, 'group')));
    }
}
