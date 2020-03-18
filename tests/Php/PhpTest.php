<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Closure;
use Countable;
use Exception;
use IteratorAggregate;
use OuterIterator;
use Php\test\assert;
use RecursiveIteratorIterator;
use RuntimeException;
use SimpleXMLElement;
use stdClass;
use Traversable;

class PhpTest extends MapTest
{
    protected function map(...$arguments): Map
    {
        return Php::map(...$arguments);
    }

    public function testHasKey(): void
    {
        assert\false(Php::hasKey('key', null));
        assert\false(Php::hasKey('key', (object)[]));
        assert\false(Php::hasKey('key', []));
        assert\false(Php::hasKey('key', Php::map()));

        assert\true(Php::hasKey('key', Php::map(['key' => null])));
        assert\true(Php::hasKey('key', ['key' => null]));

        assert\true(Php::hasKey('key', Php::map(['key' => false])));
        assert\true(Php::hasKey('key', ['key' => false]));

        assert\true(Php::hasKey('key', Php::map(['key' => 0])));
        assert\true(Php::hasKey('key', ['key' => 0]));
        assert\true(Php::hasKey('key', ['key' => 0]));

        assert\true(Php::hasKey(0, 'a'));
        assert\false(Php::hasKey(0, ''));
    }

    public function testHasValue(): void
    {
        assert\false(Php::hasValue('value', null));
        assert\false(Php::hasValue('value', []));
        assert\false(Php::hasValue('value', Php::map()));

        assert\true(Php::hasValue(100, [100]));
        assert\false(Php::hasValue('100', [100]));
        assert\true(Php::hasValue('100', [100], false));

        assert\true(Php::hasValue(100, Php::map([100])));
        assert\false(Php::hasValue('100', Php::map([100])));
        assert\true(Php::hasValue('100', Php::map([100]), false));
    }

    public function testMapReplace(): void
    {
        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            Php::traverse(Php::map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A']
            )),
            'two iterable arguments => replace'
        );

        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'c', 'd'],
            Php::traverse(Php::map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                Php::map(['c' => 'c', 'd'])
            )),
            'three iterable arguments => replace'
        );

        assert\same(
            ['k:a' => 'v:A', 'k:b' => 'v:b', 'k:c' => 'v:C'],
            Php::traverse(Php::map(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                static function ($value, $key) {
                    return Php::mapValue("v:$value")->andKey("k:$key");
                }
            )),
            'last argument is callable => replace and map'
        );
    }

    public function providerSameBehaviourTraverseAndMap(): array
    {
        $toGroup = [['a', 'a'], ['a', 'b'], ['b', 'b'], ['b', 'a']];

        return [
            'mapValue(mapNull())' => [
                [null],
                ['v'],
                static function() {
                    return Php::mapValue(Php::mapNull());
                }
            ],

            'Php\map should not merge single iterable' => [
                [2 => 'numeric-key'],
                [2 => 'numeric-key']
            ],
            'without Map\Value class' => [
                ['v1' => 'k1', 'v2' => 'k2'],
                ['k1' => 'v1', 'k2' => 'v2'],
                static function ($value, &$key) {
                    $tmp = $key;
                    $key = $value;
                    return $tmp;
                }
            ],
            'using mapBreak() and mapNull()' => [
                [1 => null, 3 => 'd'],
                ['a', 'b', 'c', 'd', 'e', 'f'],
                static function ($value) {
                    if ($value === 'e') {
                        return Php::mapBreak();
                    }
                    if (in_array($value, ['a', 'c'], true)) {
                        return null;
                    }

                    return $value === 'b' ? Php::mapNull() : $value;
                },
            ],
            'using mapValue() and mapKey()' => [
                ['VALUE', 'KEY' => 'key', 'pair' => 'flip', 'no' => 'changes'],
                ['value', 'key', 'flip' => 'pair', 'no' => 'changes'],
                static function ($value, $key) {
                    if ($value === 'value') {
                        return Php::mapValue('VALUE');
                    }
                    if ($value === 'key') {
                        return Php::mapKey('KEY');
                    }
                    if ($key === 'flip') {
                        return Php::mapValue($key)->andKey($value);
                    }

                    return Php::mapValue();
                },
            ],
            'group by a single value' => [
                [
                    'a' => [
                        0 => ['a', 'a'],
                        1 => ['a', 'b'],
                    ],
                    'b' => [
                        2 => ['b', 'b'],
                        3 => ['b', 'a'],
                    ],
                ],
                $toGroup,
                static function ($value) {
                    return Php::mapGroup($value[0]);
                },
            ],
            'group by multiple values, with key' => [
                [
                    'a' => [
                        'a' => [100 => ['a', 'a']],
                        'b' => [101 => ['a', 'b']],
                    ],
                    'b' => [
                        'b' => [102 => ['b', 'b']],
                        'a' => [103 => ['b', 'a']],
                    ],
                ],
                $toGroup,
                static function($value, $key) {
                    return Php::mapGroup($value)->andKey($key + 100);
                },
            ],
        ];
    }

    /**
     * @dataProvider providerSameBehaviourTraverseAndMap
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourTraverse($expected, $iterable, ...$mapper): void
    {
        assert\same($expected, Php::traverse($iterable, ...$mapper));
    }

    /**
     * @dataProvider providerSameBehaviourTraverseAndMap
     *
     * @param mixed $expected
     * @param mixed $iterable
     * @param mixed $mapper
     */
    public function testSameBehaviourMap($expected, $iterable, ...$mapper): void
    {
        assert\same($expected, Php::map($iterable, ...$mapper)->traverse);
    }

    public function testTraverse(): void
    {
        $emptyCallable = static function () {};

        assert\same(['key' => 'value'], Php::traverse(['key' => 'value']));
        assert\same(['key' => 'value'], Php::traverse(new ArrayObject(['key' => 'value'])));
        assert\same([], Php::traverse(Php::toArray(null, true)));
        assert\same([], Php::traverse(Php::toArray(null, true), $emptyCallable));

        assert\exception('argument $candidate must be traversable', static function () {
            Php::traverse(null);
        });
        assert\exception('argument $traversable must be traversable', static function ($emptyCallable) {
            Php::traverse(null, $emptyCallable);
        }, $emptyCallable);
        assert\same(['VALUE'], Php::traverse(Php::toArray('value', true), $this));

        if (PHP_VERSION_ID < 70200) {
            assert\same([1], Php::traverse(Php::toArray('value', true), 'count'));
        }
    }

    public function testNullBreak(): void
    {
        assert\equals((object)[], Php::mapNull());
        assert\same(Php::mapNull(), Php::mapNull());

        assert\equals((object)[], Php::mapBreak());
        assert\same(Php::mapBreak(), Php::mapBreak());

        assert\equals(Php::mapBreak(), Php::mapNull());
        assert\not\same(Php::mapBreak(), Php::mapNull());
    }

    public function testValueFunctions(): void
    {
        assert\equals(new Map\Value, Php::mapValue());
        assert\equals(new Map\Value('v'), Php::mapValue('v'));
        assert\equals(new Map\Value('v', 'k'), Php::mapValue('v', 'k'));
        assert\equals(new Map\Value('v', 'k', 'g'), Php::mapValue('v', 'k', 'g'));
        assert\equals(new Map\Value('v', 'k', 'g', 'c'), Php::mapValue('v', 'k', 'g', 'c'));

        assert\equals((new Map\Value)->andKey('k'), Php::mapKey('k'));
        assert\equals((new Map\Value)->andGroup('g'), Php::mapGroup('g'));
        assert\equals((new Map\Value)->andChildren('c'), Php::mapChildren('c'));
    }

    public function testMap(): void
    {
        assert\type(Map::class, Php::map());
        assert\equals([], Php::map()->traverse, 'args = 0');
        assert\equals(
            ['a', 'b', 'c', 'd', 'e'],
            Php::map(['a', 'b'], ['c'], ['d', 'e'])->traverse,
            'args > 1, no mapper'
        );
        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            Php::map(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e'])->traverse,
            'args > 1, no mapper, with assoc key'
        );
        assert\equals(['A', 'C', 'D', 'E'], Php::map(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })->values, 'args > 1, with mapper');

        // array_key_exists('a', array_merge(['a' => 'A'], ['b' => 'B']))
        assert\true(isset(Php::map(['a' => null], ['b' => 'B'])['a']));

        // array_merge(['a' => 'A'], ['b' => 'B'])['b']
        assert\same('B', Php::map(['a' => 'A'], ['b' => 'B'])['b']);
    }

    public function testMerge(): void
    {
        assert\same([], Php::merge(), 'args = 0');
        assert\same([], Php::merge([]));
        assert\same([], Php::merge([], new Map));
        assert\equals(['a', 'b', 'c', 'd', 'e'], Php::merge(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\equals(
            ['a', 'k' => 'e', 'c', 'd'],
            Php::merge(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals(['A', 'C', 'D', 'E'], Php::toValues(Php::merge(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');

        assert\same('B', Php::merge(['a' => 'A'], ['b' => 'B'])['b']);
    }

    public function testValues(): void
    {
        assert\same([], Php::values(), 'args = 0');
        assert\same([], Php::values([]));
        assert\same([], Php::values([], new Map));
        assert\same(['a', 'b', 'c', 'd', 'e'], Php::merge(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\same(
            ['a', 'b', 'c', 'd', 'e'],
            Php::values(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\same(['A', 2 => 'C', 'D', 'E'], Php::values(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        }), 'args > 1, with mapper');

        assert\same('B', Php::values(['a' => 'A'], ['b' => 'B'])[1]);
    }

    public function testKeys(): void
    {
        assert\same([], Php::keys(), 'args = 0');
        assert\same([], Php::keys([]));
        assert\same([], Php::keys([], new Map));
        assert\equals([0, 1, 2, 3, 4], Php::keys(['a', 'b'], ['c'], ['d', 'e']), 'args > 1, no mapper');

        assert\equals(
            [0, 'k', 1, 2],
            Php::keys(['a', 'k' => 'b'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals([0, 20, 30, 40], Php::toValues(Php::keys(['a', 'b'], ['c'], ['d', 'e'], static function ($value) {
            return $value === 1 ? null : $value * 10;
        })), 'args > 1, with mapper');

        assert\equals([10], Php::keys([10 => 'numeric']));
        assert\equals([10], Php::keys([10 => 'numeric'], static function ($key) {
            return $key;
        }));
        assert\equals([0, 1], Php::keys([10 => 'numeric'], [20 => 'numeric']));
    }

    public function testMixin(): void
    {
        assert\same([], Php::mixin(), 'args = 0');
        assert\same([], Php::mixin([]));
        assert\same([], Php::mixin([], new Map));
        assert\equals(['d', 'b'], Php::mixin(['a', 'b'], ['c'], ['d']), 'args > 1, no mapper');

        assert\equals(
            ['d', 'K' => 'c', 'k' => 'e'],
            Php::mixin(['a', 'k' => 'b', 'K' => 'c'], ['c'], ['d', 'k' => 'e']),
            'args > 1, no mapper, with assoc key'
        );

        assert\equals(['D'], Php::toValues(Php::mixin(['a', 'b'], ['c'], ['d'], static function ($value) {
            return $value === 'b' ? null : strtoupper($value);
        })), 'args > 1, with mapper');
    }

    public function testEvery(): void
    {
        assert\true(Php::every());
        assert\true(Php::every([]));
        assert\true(Php::every([' ']));
        assert\false(Php::every(['']));
        assert\true(Php::every([''], function () {
            return true;
        }));
    }

    public function testSome(): void
    {
        assert\false(Php::some());
        assert\false(Php::some([]));
        assert\false(Php::some(['', 0, null, [], false]));
        assert\true(Php::some(['', 0, null, [], false, ' ']));
        assert\false(Php::some([1, true, ' '], static function () {
            return false;
        }));
    }

    public function testTreeFunction(): void
    {
        assert\same([], Php::tree());
        assert\same([], Php::tree([], []));
        assert\same(['a', 'b', ['c'], 'c'], Php::tree(['a'], ['b', ['c']]));
        assert\same(['A', 'B', 0, 'C'], Php::tree(['a'], ['b', ['c']], static function ($value, Map\Path $it) {
            return is_string($value) ? strtoupper($value) : $it->getDepth();
        }));
    }

    public function __invoke($value, &$key): string
    {
        $key = strtolower($key);
        return strtoupper($value);
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
            'map, iterable, Map => iterable' => ['iterable', Php::map(), 'iterable', Map::class],
            'map, Map, iterable => Map' => [Map::class, Php::map(), Map::class, 'iterable'],
            'map => Countable (implements)' => [
                Countable::class, Php::map(),
                Countable::class,
                Traversable::class,
                'iterable',
                ArrayAccess::class
            ],
            'path => Countable (extends)' => [
                RecursiveIteratorIterator::class,
                new Map\Path(Php::map()),
                RecursiveIteratorIterator::class,
                OuterIterator::class,
                'iterable'
            ],
            'path => not callable' => [
                '',
                new Map\Path(Php::map()),
                RecursiveIteratorIterator::class,
                OuterIterator::class,
                'iterable',
                'callable'
            ],
        ];
    }

    public function testToArray(): void
    {
        assert\equals(['key' => 'value'], Php::toArray(['key' => 'value']));
        assert\equals(['key' => 'value'], Php::toArray(new ArrayObject(['key' => 'value'])));
        assert\equals([], Php::toArray(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            Php::toArray(null);
        });
    }

    public function testToValues(): void
    {
        assert\equals(['value'], Php::toValues(['key' => 'value']));
        assert\equals(['value'], Php::toValues(new ArrayObject(['key' => 'value'])));
        assert\equals([], Php::toValues(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            Php::toValues(null);
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
        assert\same($expected, Php::str($subject, ...$replacements));
    }

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
     * @dataProvider providerIsCallable
     *
     * @param bool $expected
     * @param array $args
     */
    public function testIsCallable($expected, ...$args): void
    {
        assert\same($expected, Php::isCallable(...$args));
    }

    public function providerIsCallable(): array
    {
        return [
            'closure' => [true, static function () {}],
            'closure tolerant' => [true, static function () {}, false],
            'closure strict' => [true, static function () {}, true],
            '__invoke' => [true, $this],
            '__invoke tolerant' => [true, $this, false],
            '__invoke strict' => [true, $this, true],
            '[c, m]' => [false, ['c', 'm']],
            '[c, m] tolerant' => [true, ['c', 'm'], false],
            '[c, m] strict' => [false, ['c', 'm'], true],
            '[c, m, third]' => [false, ['c', 'm', 'third']],
            '[c, m, third] tolerant' => [false, ['c', 'm', 'third'], false],
            '[c, m, third] strict' => [false, ['c', 'm', 'third'], true],
            '[$this, string]' => [false, [$this, 'string']],
            '[$this, string] tolerant' => [true, [$this, 'string'], false],
            '[$this, string] strict' => [false, [$this, 'string'], true],
            '[static, public]' => [true, [__CLASS__, 'staticPublic']],
            '[static, public] tolerant' => [true, [__CLASS__, 'staticPublic'], false],
            '[static, public] strict' => [true, [__CLASS__, 'staticPublic'], true],
            'count' => [false, 'count'],
            'count tolerant' => [true, 'count', false],
            'count strict' => [false, 'count', true],
        ];
    }

    /**
     * ignore
     */
    public static function staticPublic(): void
    {
    }

    public function providerIter(): array
    {
        $proxy = function ($traversable): IteratorAggregate
        {
            return new class($traversable) implements IteratorAggregate
            {
                private $traversable;

                public function __construct($traversable)
                {
                    $this->traversable = $traversable instanceof Closure ? $traversable($this) : $traversable;
                }

                public function getIterator()
                {
                    return $this->traversable;
                }
            };
        };
        return [
            'intern traversable classes are wrapped around IteratorIterator' => [
                'expected' => [],
                'proxy' => $proxy(new SimpleXMLElement('<root/>')),
            ],
            '$inner::getIterator returns same instance' => [
                'expected' => new RuntimeException('Implementation $candidate::getIterator returns the same instance'),
                'proxy' => $proxy(static function($that) {return $that;}),
            ],
            '$proxy::getIterator is too deep' => [
                'expected' => new RuntimeException('$candidate::getIterator is too deep'),
                'proxy' => $proxy(static function() use($proxy) {
                    return $proxy(static function() use($proxy) {
                        return $proxy(static function() use($proxy) {
                            return $proxy(static function() use($proxy) {
                                return $proxy(static function() use($proxy) {
                                    return $proxy(static function() use($proxy) {
                                        return $proxy(static function() use($proxy) {
                                            return $proxy(static function() use($proxy) {
                                                return $proxy(static function() use($proxy) {
                                                    return $proxy(static function() use($proxy) {
                                                        return $proxy(static function() use($proxy) {
                                                            return $proxy(static function() {});
                                                        });
                                                    });
                                                });
                                            });
                                        });
                                    });
                                });
                            });
                        });
                    });
                })
            ],
            '$inner depth = 3' => [
                'expected' => ['depth' => 3],
                'proxy' => $proxy(static function() use($proxy) {
                    return $proxy(static function() use($proxy) {
                        return $proxy(new ArrayIterator(['depth' => 3]));
                    });
                }),
            ],
            'simple iterator' => [
                'expected' => ['a' => 'a', 'b' => ['c' => 'd']],
                'proxy' => new ArrayIterator(['a' => 'a', 'b' => ['c' => 'd']]),
            ],
            'simple array' => [
                'expected' => ['a', 'b', 'c'],
                'proxy' => ['a', 'b', 'c'],
            ],
            'empty array' => [
                'expected' => [],
                'proxy' => [],
            ],
            'null => exception' => [
                'expected' => new RuntimeException('Argument $candidate must be iterable'),
                null
            ],
            '=> EmptyIterator' => [
                'expected' => [],
            ],
        ];
    }

    /**
     * @dataProvider providerIter
     *
     * @param array|Exception $expected
     * @param callable|iterable ...$proxy
     */
    public function testIter($expected, ...$proxy): void
    {
        assert\equals\trial($expected, static function () use ($proxy) {
            return Php::arr(Php::iter(...$proxy));
        });
    }
}
