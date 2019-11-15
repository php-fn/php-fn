<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use Closure;
use Php\test\assert;
use PHPUnit\Framework\TestCase;

class SortTest extends TestCase
{
    public function testSort(): void
    {
        $sortSort = static function (...$args) {
            return Sort::sort(...$args);
        };

        $phpSort = static function (...$args) {
            return Php::sort(...$args);
        };

        $this->assertSimpleSort($sortSort);
        $this->assertSimpleSort($phpSort);
        $this->assertMultiSort($sortSort);
        $this->assertMultiSort($phpSort);
    }

    private function assertSimpleSort(Closure $sort): void
    {
        $input = ['bb', 'aaa', 'cc', 'c'];
        assert\same(
            ['aaa', 'bb', 'c', 'cc'],
            array_values($sort($input, Sort::REG))
        );
        assert\same(
            ['c', 'bb', 'cc', 'aaa'],
            array_values($sort(Php::gen($input), static function ($value) {
                yield strlen($value);
            }))
        );
        $input = ['a' => 'C', 'd' => 'A', 'c' => 'B', 'b' => 'D'];
        assert\same(['d' => 'A', 'c' => 'B', 'a' => 'C', 'b' => 'D'], $sort($input));
        assert\same(['b' => 'D', 'a' => 'C', 'c' => 'B', 'd' => 'A'], $sort($input, Sort::DESC));
        assert\same(['a' => 'C', 'b' => 'D', 'c' => 'B', 'd' => 'A'], $sort($input, Sort::KEYS));
        assert\same(['d' => 'A', 'c' => 'B', 'b' => 'D', 'a' => 'C'], $sort($input, Sort::KEYS | Sort::DESC));

        $nat = ['a1' => 'a1', 'A12' => 'A12', 'A2' => 'A2', 'a11' => 'a11'];
        assert\same(
            ['A2' => 'A2', 'A12' => 'A12', 'a1' => 'a1', 'a11' => 'a11'],
            $sort($nat, Sort::NAT)
        );
        assert\same(
            ['a1' => 'a1', 'A2' => 'A2', 'a11' => 'a11', 'A12' => 'A12'],
            $sort($nat, Sort::NAT | Sort::CASE)
        );

        $input = [
            'a' => ['b' => $nat, 'd' => [], 'c' => []],
            'c' => ['a' => $nat],
            'b' => ['b' => [], 'a' => $nat]
        ];
        $natSorted = ['a1' => 'a1', 'A2' => 'A2', 'a11' => 'a11', 'A12' => 'A12'];
        assert\same(
            [
                'c' => [
                    'a' => $natSorted,
                ],
                'b' => [
                    'a' => $natSorted,
                    'b' => [],
                ],
                'a' => [
                    'b' => $natSorted,
                    'c' => [],
                    'd' => [],
                ],
            ],
            $sort(
                $input,
                Sort::KEYS | Sort::DESC,
                Sort::KEYS,
                Sort::NAT | Sort::CASE
            )
        );

        $natSorted = ['a1' => 'a1', 'A2' => 'A2', 'A12' => 'A12', 'a11' => 'a11'];
        assert\same(
            [
                'a' => [
                    'c' => [],
                    'd' => [],
                    'b' => $natSorted,
                ],
                'b' => [
                    'b' => [],
                    'a' => $natSorted,
                ],
                'c' => [
                    'a' => $natSorted
                ],
            ],
            $sort(
                $input,
                Sort::KEYS,
                static function ($value, $key, $i) {
                    yield $value ? $i : -$i;
                },
                Sort::by(static function ($value) {
                    yield $value === 'a11' ? '' : $value;
                }, Sort::DESC | Sort::STR)
            )
        );
    }

    private function assertMultiSort(Closure $sort): void
    {
        $input = [
            'k1' => 'b',
            'k4' => 'a',
            'k3' => 'a',
            'k2' => 'a',
        ];

        assert\same(
            ['a', 'a', 'a', 'b'],
            array_values($sort($input, [Sort::NAT | Sort::ASC]))
        );

        assert\same([
            'k4' => 'a',
            'k3' => 'a',
            'k2' => 'a',
            'k1' => 'b',
        ], $sort($input, [Sort::NAT, Sort::KEYS | Sort::DESC]));

        assert\same([
            'k2' => 'a',
            'k3' => 'a',
            'k4' => 'a',
            'k1' => 'b',
        ], $sort($input, [Sort::ASC, Sort::KEYS]));

        $input = [
            'a1' => $a1 = self::obj(['c1' => 'a1', 'c2' => 'x', 'c3' => 'x', ]),
            'A1' => $A1 = self::obj(['c1' => 'A1', 'c2' => 'x', 'c3' => 'y', ]),
            'b' => $b = self::obj(['c1' => 'b', 'c2' => 'x', 'c3' => 'x', ]),
            'A11' => $A11 = self::obj(['c1' => 'A11', 'c2' => 'y', 'c3' => 'y', ]),
            'a2' => $a2 = self::obj(['c1' => 'a2', 'c2' => 'y', 'c3' => 'x', ]),
        ];

        assert\same(
            [
                'a2' => $a2,
                'A11' => $A11,
                'b' => $b,
                'a1' => $a1,
                'A1' => $A1,
            ],
            $sort($input, [
                'c2' => Sort::DESC,
                'c3' => Sort::ASC,
                Sort::KEYS | Sort::DESC,
            ])
        );

        assert\same(
            [
                'a2' => $a2,
                'A11' => $A11,
                'a1' => $a1,
                'b' => $b,
                'A1' => $A1,
            ],
            $sort($input, [
                'c2' => Sort::DESC,
                'c3' => Sort::ASC,
                Sort::KEYS | Sort::ASC,
            ])
        );

        assert\same(
            [
                'a2' => $a2,
                'A11' => $A11,
                'a1' => $a1,
                'b' => $b,
                'A1' => $A1,
            ],
            $sort($input, [
                'c2' => Sort::DESC,
                'c3' => Sort::ASC,
                Sort::KEYS | Sort::ASC,
            ])
        );

        assert\same(
            [
                'A1' => $A1,
                'a2' => $a2,
                'A11' => $A11,
                'a1' => $a1,
                'b' => $b,
            ],
            $sort($input, [
                'method()',
                'c1'
            ])
        );

        assert\same(
            [
                'A1' => $A1,
                'a2' => $a2,
                'A11' => $A11,
                'a1' => $a1,
                'b' => $b,
            ],
            $sort($input, [
                static function ($obj) {
                    return $obj->method();
                },
                'c1'
            ])
        );

        assert\same(
            [
                'b' => $b,
                'a1' => $a1,
                'A11' => $A11,
                'a2' => $a2,
                'A1' => $A1,
            ],
            $sort($input, [
                'method()' => Sort::DESC,
                'c1' => Sort::DESC,
            ])
        );

        assert\same(
            [
                'b' => $b,
                'A1' => $A1,
                'a1' => $a1,
                'A11' => $A11,
                'a2' => $a2,
            ],
            $sort($input, [
                '$prop',
                Sort::KEYS,
            ])
        );

        assert\same(
            [
                'a2' => $a2,
                'A11' => $A11,
                'a1' => $a1,
                'A1' => $A1,
                'b' => $b,
            ],
            $sort($input, [
                new Sort(static function ($value) {
                    return $value->prop;
                }, Sort::DESC),
                Sort::KEYS | Sort::DESC,
            ])
        );
    }

    private static function obj(array $data)
    {
        return new class($data) implements ArrayAccess {
            use ArrayAccessTrait;
            use PropertiesTrait;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            protected function resolveProp()
            {
                return str_ireplace('a', 'c', $this['c1']);
            }

            public function method(): bool
            {
                return $this['c2'] === $this['c3'];
            }
        };
    }
}
