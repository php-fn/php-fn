<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\test\assert;

class PropertiesReadWrite
{
    use PropertiesReadWriteTrait;
    private const DEFAULT = ['a' => null];

    public function __construct($properties)
    {
        $this->initProperties($properties);
    }
}

/**
 * @coversDefaultClass PropertiesReadWriteTrait
 */
class PropertiesReadWriteTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::property
     * @covers ::initProperties
     */
    public function testTrait(): void
    {
        assert\exception(
            str('magic properties (b,c) are not defined in %s::DEFAULT', PropertiesReadWrite::class),
            static function () {
                new PropertiesReadWrite(['b' => 'B', 'a' => 'A', 'c' => 'C']);
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
            protected const DEFAULT = ['a' => 'A', 'b' => null];

            public function __construct()
            {
                $this->initProperties();
            }
        });

        self::assertAB('A', 'B', new class(['b' => 'B'])
        {
            use PropertiesReadWriteTrait;

            public function __construct($properties)
            {
                $this->initProperties($properties);
            }

            private const DEFAULT = ['a' => 'A', 'b' => null];
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

            protected function resolveD(): string
            {
                return 'D';
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


        $obj = new class {
            use PropertiesReadWriteTrait;

            private $unresolved = [];
            protected function propertyMethodInvoke(string $name, ...$args)
            {
                if ($args) {
                    $this->unresolved[$name] = $args;
                    return $args[0];
                }
                return $this->{$this->propertyMethod($name)->name}(...$this->unresolved[$name] ?? []);
            }

            protected function resolveProp(...$args): array
            {
                return [$args[0] ?? 'A'];
            }
        };

        assert\same(['A'], $obj->prop);
        unset($obj->unknown, $obj->prop);
        assert\same(['A'], $obj->prop);
        $obj->prop = 'B';
        assert\same(['B'], $obj->prop);
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
