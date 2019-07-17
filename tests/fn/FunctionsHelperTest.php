<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use ArrayAccess;
use ArrayObject;
use Closure;
use Countable;
use function fn\_\toArray;
use function fn\_\toTraversable;
use function fn\_\toValues;
use fn\Map\Path;
use fn\Map\RowMapper;
use fn\test\assert;
use OuterIterator;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use stdClass;
use Traversable;

/**
 */
class FunctionsHelperTest extends TestCase
{
    /**
     */
    public function testToTraversable(): void
    {
        $ar = [true];
        $it = new ArrayObject($ar);
        assert\same($ar, toTraversable([true]));
        assert\same($it, toTraversable($it));
        assert\equals($it, toTraversable(new ArrayObject($ar)));
        assert\not\same($it, toTraversable(new ArrayObject($ar)));
        assert\same(['string'], toTraversable('string', true));
        assert\same([], toTraversable(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            toTraversable('string');
        });
    }

    /**
     */
    public function testToArray(): void
    {
        assert\equals(['key' => 'value'], toArray(['key' => 'value']));
        assert\equals(['key' => 'value'], toArray(new ArrayObject(['key' => 'value'])));
        assert\equals([], toArray(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            toArray(null);
        });
    }

    /**
     */
    public function testToValues(): void
    {
        assert\equals(['value'], toValues(['key' => 'value']));
        assert\equals(['value'], toValues(new ArrayObject(['key' => 'value'])));
        assert\equals([], toValues(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            toValues(null);
        });
    }

    /**
     * @dataProvider providerStr
     *
     * @param string $expected
     * @param string $subject
     * @param array $replacements
     */
    public function testStr($expected, $subject, ...$replacements): void
    {
        assert\same($expected, str($subject, ...$replacements));
    }

    /**
     * @return array[]
     */
    public function providerStr(): array
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

    /**
     * @dataProvider providerType
     *
     * @param string $expected
     * @param mixed $var
     * @param mixed ...$types
     */
    public function testType(string $expected, $var, ...$types): void
    {
        assert\same($expected, type($var, ...$types));
    }

    /**
     * @return array
     */
    public static function providerType(): array
    {
        $count = (static function (): callable {return 'count';})();
        $method = (static function (): callable {return [static::class, 'providerType'];})();
        $callable = new RowMapper(null);
        return [
            "null = ''" => ['', null],
            "'' => string" => ['string', ''],
            'count, callable, string => callable' => ['callable', $count, 'callable', 'string'],
            "'', string, string => string" => ['string', '', 'string', 'string'],
            "'', string, int => ''" => ['', '', 'string', 'int'],
            'false => bool' => ['bool', false],
            'false, bool, bool => bool' => ['bool', false, 'bool', 'bool'],
            'true => bool' => ['bool', true],
            "true, bool, int => ''" => ['', true, 'bool', 'int'],
            '0 => int' => ['int', 0],
            '0, int, int => int' => ['int', 0, 'int', 'int'],
            "0, int, bool => ''" => ['', 0, 'int', 'bool'],
            '[] => array' => ['array', []],
            'method, callable, array => callable' => ['callable', $method, 'callable', 'array'],
            '[], array, array => array' => ['array', [], 'array', 'array'],
            '[], array, iterable => array' => ['array', [], 'array', 'iterable'],
            '[], iterable, array => iterable' => ['iterable', [], 'iterable', 'array'],
            "[], array, int => ''" => ['', [], 'array', 'int'],
            'function => callable' => ['callable', static function () {}],
            'RowMapper => RowMapper' => [RowMapper::class, $callable],
            'Closure(RowMapper) => callable' => ['callable', Closure::fromCallable($callable)],
            '(object)[] => stdClass' => [stdClass::class, (object)[]],
            'map, iterable, Map => iterable' => ['iterable', map(), 'iterable', Map::class],
            'map, Map, iterable => Map' => [Map::class, map(), Map::class, 'iterable'],
            'map => Countable (implements)' => [
                Countable::class, map(),
                Countable::class,
                Traversable::class,
                'iterable',
                ArrayAccess::class
            ],
            'path => Countable (extends)' => [
                RecursiveIteratorIterator::class,
                new Path(map()),
                RecursiveIteratorIterator::class,
                OuterIterator::class,
                'iterable'
            ],
            'path => not callable' => [
                '',
                new Path(map()),
                RecursiveIteratorIterator::class,
                OuterIterator::class,
                'iterable',
                'callable'
            ],
        ];
    }
}
