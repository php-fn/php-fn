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
use LogicException;
use OutOfRangeException;

/**
 * @covers Row
 */
class RowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array[]
     */
    public function providerArrayAccess()
    {
        $errorOutOfRange = new OutOfRangeException("missing offset 'key'");
        $errorReadOnly = new LogicException('row has read-only access');
        $errorRead = new DomainException('read access is possible only for: iterable | ArrayAccess');
        $errorWrite = new DomainException('write access is possible only for: array | ArrayAccess');

        $expectedEmpty = [
            'is'      => false,
            'get'     => $errorOutOfRange,
            'del'     => null,
            'del-is'  => false,
            'del-get' => $errorOutOfRange,
            'set'     => null,
            'set-is'  => true,
            'set-get' => 'set',
        ];

        $expectedIterable = [
            'del' => $errorWrite,
            'del-is' => false,
            'set' => $errorWrite,
            'set-is' => false,
            'set-get' => $errorOutOfRange,
        ];

        $expected = fn\map($expectedEmpty, [
            'is'  => true,
            'get' => 'value',
        ]);

        $expectedExceptions = [
            'is'      => $errorRead,
            'get'     => $errorRead,
            'del'     => $errorWrite,
            'del-is'  => $errorRead,
            'del-get' => $errorRead,
            'set'     => $errorWrite,
            'set-is'  => $errorRead,
            'set-get' => $errorRead,
        ];

        $expectedReadOnly = [
            'del'     => $errorReadOnly,
            'set'     => $errorReadOnly,
            'set-is'  => false,
            'set-get' => $errorOutOfRange,
        ];

        return [
            'wrong type' => [$expectedExceptions, new Row(null)],
            'wrong type read-only' => [
                fn\map($expectedExceptions, ['del' => $errorReadOnly, 'set' => $errorReadOnly]),
                new Row(null, true)
            ],

            'empty array' => [$expectedEmpty, new Row([])],
            'empty array read-only' => [fn\map($expectedEmpty, $expectedReadOnly), new Row([], true)],
            'array' => [$expected, new Row(['key' => 'value'])],

            'empty array access' => [$expectedEmpty, new Row(fn\map([]))],
            'empty array access read-only' => [fn\map($expectedEmpty, $expectedReadOnly), new Row(fn\map([]), true)],
            'array access' => [$expected, new Row(fn\map(['key' => 'value']))],

            'empty iterable' => [fn\map($expectedEmpty, $expectedIterable), new Row(new Tree([]))],
            'empty iterable read-only' => [
                fn\map($expectedEmpty, $expectedIterable, $expectedReadOnly),
                new Row(new Tree([]), true)
            ],
            'iterable' => [fn\map($expected, $expectedIterable, [
                'del-is'  => true,
                'del-get' => 'value',
                'set-is'  => true,
                'set-get' => 'value',
            ]), new Row(new Tree(['key' => 'value']))],
        ];
    }

    /**
     * @dataProvider providerArrayAccess
     *
     * @covers Row::offsetExists
     * @covers Row::offsetGet
     * @covers Row::offsetSet
     * @covers Row::offsetUnset
     *
     * @param iterable $expected
     * @param Row      $row
     */
    public function testArrayAccess($expected, Row $row)
    {
        assert\same\trial($expected['is'], function(Row $row) {
            return isset($row['key']);
        }, $row);
        assert\same\trial($expected['get'], function(Row $row) {
            return $row['key'];
        }, $row);

        assert\same\trial($expected['del'], function(Row $row) {
            unset($row['key']);
        }, $row);
        assert\same\trial($expected['del-is'], function(Row $row) {
            return isset($row['key']);
        }, $row);
        assert\same\trial($expected['del-get'], function(Row $row) {
            return $row['key'];
        }, $row);

        assert\same\trial($expected['set'], function(Row $row) {
            $row['key'] = 'set';
        }, $row);
        assert\same\trial($expected['set-is'], function(Row $row) {
            return isset($row['key']);
        }, $row);
        assert\same\trial($expected['set-get'], function(Row $row) {
            return $row['key'];
        }, $row);
    }

    /**
     * @covers Row::__get
     */
    public function testProperties()
    {
        $row = new Row(['v' => 'V', 'k' => 'K', 'g' => 'G', 'c' => 'C']);
        assert\false($row->readOnly);

        $missingOffset = new OutOfRangeException("missing offset ''");
        assert\exception($missingOffset, function(Row $row) {
            $row->value;
        }, $row);
        assert\same('V', $row->andValue('v')->value);
        assert\same(['V', 'V'], $row->andValue(['v', 'v'])->value);
        assert\same('V', $row->andValue(function(Row $row) {
            return $row['v'];
        })->value);

        assert\exception($missingOffset, function(Row $row) {
            $row->key;
        }, $row);
        assert\same('K', $row->andKey('k')->key);
        assert\same(['K', 'K'], $row->andKey(['k', 'k'])->key);
        assert\same('K', $row->andKey(function(Row $row) {
            return $row['k'];
        })->key);

        assert\exception($missingOffset, function(Row $row) {
            $row->group;
        }, $row);
        assert\same('G', $row->andGroup('g')->group);
        assert\same(['G', 'G'], $row->andGroup(['g', 'g'])->group);
        assert\same('G', $row->andGroup(function(Row $row) {
            return $row['g'];
        })->group);

        assert\exception($missingOffset, function(Row $row) {
            $row->children;
        }, $row);
        assert\same('C', $row->andChildren('c')->children);
        assert\same(['C', 'C'], $row->andChildren(['c', 'c'])->children);
        assert\same('C', $row->andChildren(function(Row $row) {
            return $row['c'];
        })->children);
    }

    /**
     * @covers Row::__get
     * @covers fn\mapRow()
     */
    public function testTraverse()
    {
        $result = [
            ['col1' => 'id-1', 'col2' => 'a', 'col3' => 'a'],
            ['col1' => 'id-2', 'col2' => 'b', 'col3' => 'a'],
            ['col1' => 'id-3', 'col2' => 'c', 'col3' => 'a'],
            ['col1' => 'id-4', 'col2' => 'c', 'col3' => 'a'],
            ['col1' => 'id-5', 'col2' => 'c', 'col3' => 'b'],
        ];

        assert\same($result, fn\traverse($result, function(array $row, $key) {
            return ($key % 2) ? new Row($row) : fn\mapRow($row);
        }));

        assert\same([
            'id-1' => ['col1' => 'id-1', 'col2' => 'a', 'col3' => 'a'],
            'id-2' => ['col1' => 'id-2', 'col2' => 'b', 'col3' => 'a'],
            'id-3' => ['col1' => 'id-3', 'col2' => 'c', 'col3' => 'a'],
            'id-4' => ['col1' => 'id-4', 'col2' => 'c', 'col3' => 'a'],
            'id-5' => ['col1' => 'id-5', 'col2' => 'c', 'col3' => 'b'],
        ], fn\traverse($result, function(array $row, $key) {
            return ($key % 2) ? (new Row($row))->andKey('col1') : fn\mapRow($row, 'col1');
        }));

        assert\same(['a', 'b', 'c', 'c', 'c'], fn\traverse($result, function(array $row, $key) {
            return ($key % 2) ? (new Row($row))->andValue('col2') : fn\mapRow($row, null, 'col2');
        }));

        assert\same([
            'a' => ['a' => ['id-1' => 'a-a']],
            'b' => ['a' => ['id-2' => 'b-a']],
            'c' => [
                'a' => ['id-3' => 'c-a', 'id-4' => 'c-a'],
                'b' => ['id-5' => 'c-b'],
            ],
        ], fn\traverse($result, function(array $row, $key) {
            $resolveValue = function(Row $row) {
                return "{$row['col2']}-{$row['col3']}";
            };
            if ($key % 2) {
                return (new Row($row))->andGroup(['col2', 'col3'])->andValue($resolveValue)->andKey('col1');
            }
            return fn\mapRow($row, 'col1', $resolveValue)->andGroup(['col2', 'col3']);
        }));
    }
}
