<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\test\assert;

/**
 * @property $a
 * @property-read $foo
 * @property-read $bar
 * @property-read $inc
 * @property-read $const
 */
class Properties
{
    use PropertiesReadWriteTrait;

    protected const TRAIT_PROPERTIES = ['defaults' => ['a' => null], 'resolve' => '_*', 'compile' => '*_'];

    public function __construct($properties = [])
    {
        $this->initProperties($properties);
    }

    /**
     * @see $foo
     * @return string
     */
    protected function _foo(): string
    {
        return 'foo';
    }

    /**
     * @see $bar
     * @return string
     */
    protected function bar_(): string
    {
        return 'bar';
    }

    /**
     * @see $count
     * @return int
     */
    protected function _inc(): int
    {
        static $count = 0;
        return $count++;
    }

    /**
     * @see $const
     * @return int
     */
    protected function const_(): int
    {
        static $count = 0;
        return $count++;
    }
}

/**
 * @coversDefaultClass PropertiesReadWriteTrait
 */
class PropertiesReadWriteTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers PropertiesReadWriteTrait::property
     * @covers PropertiesReadWriteTrait::initProperties
     */
    public function testTrait(): void
    {
        assert\exception(
            str("magic properties (b,c) are not defined in %s::TRAIT_PROPERTIES['defaults']", Properties::class),
            static function () {
                new Properties(['b' => 'B', 'a' => 'A', 'c' => 'C']);
            }
        );

        self::assertAB('A', null, new class(['a' => 'A', 'b' => null])
        {
            use PropertiesReadWriteTrait;

            public function __construct($properties)
            {
                $this->initProperties($properties);
            }
        });

        self::assertAB('A', null, new class
        {
            use PropertiesReadWriteTrait;
            protected const TRAIT_PROPERTIES = ['defaults' => ['a' => 'A', 'b' => null]];

            public function __construct()
            {
                $this->initProperties();
            }
        });

        self::assertAB('A', 'B', new class(['b' => 'B'])
        {
            use PropertiesReadWriteTrait;
            private const TRAIT_PROPERTIES = ['defaults' => ['a' => 'A', 'b' => null]];

            public function __construct($properties)
            {
                $this->initProperties($properties);
            }
        });

        self::assertAB('A', 'B', $obj = new class
        {
            use PropertiesReadWriteTrait;

            protected function resolveA(...$args): string
            {
                $args && $this->properties['a'] = $args[0];
                return $this->properties['a'] ?? 'A';
            }

            protected function resolveB(...$args): string
            {
                $args && $this->properties['b'] = $args[0];
                return $this->properties['b'] ?? 'B';
            }

            protected function resolveD(): Map\Value
            {
                return mapValue('D');
            }
        });
        assert\same('D', $obj->d);
        assert\exception(
            str('class %s has read-only access for the magic-property: d', get_class($obj)),
            static function ($obj) {
               $obj->d = 'd';
            },
            $obj
        );

        $obj = new Properties;
        assert\same('foo', $obj->foo);
        assert\same('foo', $obj->foo);
        assert\same('bar', $obj->bar);
        assert\same('bar', $obj->bar);
        assert\same(0, $obj->inc);
        assert\same(1, $obj->inc);
        assert\same(2, $obj->inc);
        assert\same(0, $obj->const);
        assert\same(0, $obj->const);


        $obj = new class extends Properties {
            protected const TRAIT_PROPERTIES = ['resolve' => '_get*', 'compile' => '*get_'];

            /**
             * @see $foo
             * @return string
             */
            protected function _getFoo(): string
            {
                return 'bar';
            }

            /**
             * @see $const
             * @return int
             */
            protected function constGet_(): int
            {
                static $count = 10;
                return $count++;
            }
        };
        assert\same('bar', $obj->foo);
        assert\same('bar', $obj->foo);
        assert\same('bar', $obj->bar);
        assert\same('bar', $obj->bar);
        assert\same(3, $obj->inc);
        assert\same(4, $obj->inc);
        assert\same(5, $obj->inc);
        assert\same(10, $obj->const);
        assert\same(10, $obj->const);
    }

    private static function assertAB($a, $b, $obj): void
    {
        assert\true(isset($obj->a));
        assert\true(isset($obj->b));
        assert\false(isset($obj->c));
        assert\same($a, $obj->a);
        assert\same($b, $obj->b);
        assert\exception(str('missing magic-property c in %s', get_class($obj)), static function ($obj) {
            $obj->c;
        }, $obj);
        unset($obj->a, $obj->b, $obj->c);
        assert\true(isset($obj->a));
        assert\true(isset($obj->b));
        assert\false(isset($obj->c));
        assert\same(null, $obj->a);
        assert\same(null, $obj->b);
    }
}
