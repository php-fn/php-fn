<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use Closure;
use Countable;
use Php\test\assert;
use OuterIterator;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use stdClass;
use Traversable;

class PhpTest extends TestCase
{
    /**
     * @dataProvider providerType
     *
     * @param string $expected
     * @param mixed $var
     * @param mixed ...$types
     */
    public function testType(string $expected, $var, ...$types): void
    {
        assert\same($expected, Php::type($var, ...$types));
    }

    public static function providerType(): array
    {
        $count = (static function (): callable {return 'count';})();
        $method = (static function (): callable {return [static::class, 'providerType'];})();
        $callable = new Map\RowMapper(null);
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
            'RowMapper => RowMapper' => [Map\RowMapper::class, $callable],
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
                new Map\Path(map()),
                RecursiveIteratorIterator::class,
                OuterIterator::class,
                'iterable'
            ],
            'path => not callable' => [
                '',
                new Map\Path(map()),
                RecursiveIteratorIterator::class,
                OuterIterator::class,
                'iterable',
                'callable'
            ],
        ];
    }
}
