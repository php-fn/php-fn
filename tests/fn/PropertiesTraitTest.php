<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use ArrayAccess;
use Countable;
use fn\test\assert;
use PHPUnit\Framework\TestCase;

/**
 * @property $a
 * @property $rw
 * @property-read array $x
 * @property-read array $y
 * @property-read string $z
 * @property string $string
 * @property int $int
 * @property-read float $float
 * @property-read array $array
 * @property-read iterable $iterable
 * @property-read bool $bool
 * @property-read callable $callable
 * @property-read PropertiesReadWrite $self
 * @property-read ArrayAccess $access
 */
class PropertiesReadWrite
{
    use PropertiesTrait;
    use PropertiesTrait\Init;
    private const DEFAULT = ['a' => null];

    public function __construct($properties, bool $init = true)
    {
        $init && $this->initProperties($properties);
    }

    /**
     * @see $x
     * @return array
     */
    protected function resolveX(): array
    {
        static $count = 0;
        return [$count++ => $this->properties['x'] ?? null];
    }

    /**
     * @see $y
     * @return Map\Value
     */
    protected function resolveY(): Map\Value
    {
        static $count = 0;
        return mapValue([$count++ => $this->properties['y'] ?? null]);
    }

    /**
     * @see $z
     * @return string
     */
    protected function resolveZ(): string
    {
        return 'Z';
    }

    /**
     * @see $rw
     * @param mixed ...$args
     * @return mixed
     */
    protected function resolveRw(...$args)
    {
        static $count = 0;
        if ($args) {
            return $this->properties['_rw'] = $args[0];
        }
        return $this->properties['_rw'] ?? mapValue($count++);
    }

    /**
     * @see $string
     * @param mixed ...$args
     * @return string|null
     */
    protected function resolveString(...$args): ?string
    {
        if ($args) {
            $this->properties['string'] = $args[0];
            return null;
        }
        return (string)($this->properties['string'] ?? null);
    }

    /**
     * @see $int
     * @param mixed ...$args
     * @return int|null
     */
    protected function resolveInt(...$args): ?int
    {
        if ($args) {
            $this->properties['int'] = $args[0];
            return null;
        }
        $value = $this->properties['int'] ?? null;
        return (int)(is_array($value) || $value instanceof Countable ? count($value) : $value);
    }

    protected function resolveFloat(): float
    {
        return .0;
    }

    protected function resolveArray(): array
    {
        return [];
    }

    protected function resolveIterable(): iterable
    {
        return [];
    }

    protected function resolveBool(): bool
    {
        return true;
    }

    protected function resolveCallable(): callable
    {
        return static function () {};
    }

    protected function resolveSelf(): self
    {
        return $this;
    }

    protected function resolveAccess(): ArrayAccess
    {
        return map();
    }
}

/**
 * @coversDefaultClass PropertiesTrait
 */
class PropertiesTraitTest extends TestCase
{
    /**
     * @covers ::propertyResolved
     */
    public function testPropertyResolved(): void
    {
        $obj = new PropertiesReadWrite([
            'string' => null,
            'int' => null,
            'float' => null,
            'array' => null,
            'iterable' => null,
            'bool' => null,
            'callable' => null,
            'self' => null,
            'access' => null,
        ], false);

        assert\same('', $obj->string);
        $obj->string = map(['a', 'b']);
        assert\same("a\nb", $obj->string);

        assert\same(0, $obj->int);
        $obj->int = map(['a', 'b']);
        assert\same(2, $obj->int);

        assert\same(.0, $obj->float);
        assert\same([], $obj->array);
        assert\same([], $obj->iterable);
        assert\equals(static function () {}, $obj->callable);
        assert\same($obj, $obj->self);
        assert\equals(map(), $obj->access);
    }

    /**
     * @covers ::property
     */
    public function testTrait(): void
    {
        assert\exception(
            str('magic properties (b,c) are not defined in %s::DEFAULT', PropertiesReadWrite::class),
            static function () {
                new PropertiesReadWrite(['b' => 'B', 'a' => 'A', 'c' => 'C', 'z' => 'Z']);
            }
        );

        self::assertAB('A', null, new class(['a' => 'A', 'b' => null])
        {
            use PropertiesTrait;
            use PropertiesTrait\Init;

            public function __construct($properties)
            {
                $this->initProperties($properties);
            }
        });

        self::assertAB('A', null, new class
        {
            use PropertiesTrait;
            use PropertiesTrait\Init;
            protected const DEFAULT = ['a' => 'A', 'b' => null];

            public function __construct()
            {
                $this->initProperties();
            }
        });

        self::assertAB('A', 'B', new class(['b' => 'B'])
        {
            use PropertiesTrait;
            use PropertiesTrait\Init;
            private const DEFAULT = ['a' => 'A', 'b' => null];

            public function __construct($properties)
            {
                $this->initProperties($properties);
            }
        });

        self::assertAB('A', 'B', $obj = new class
        {
            use PropertiesTrait;

            protected function resolveA(...$args)
            {
                $args && $this->properties['a'] = $args[0];
                return $this->properties['a'] ?? 'A';
            }

            protected function resolveB(...$args)
            {
                $args && $this->properties['b'] = $args[0];
                return $this->properties['b'] ?? 'B';
            }

            protected function resolveD()
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
            use PropertiesTrait;

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

        $obj = new PropertiesReadWrite(['x' => 'foo', 'z' => 'bar']);
        assert\same(['foo'], $obj->x);
        assert\same(['foo'], $obj->x);
        assert\same([null], $obj->y);
        assert\same([1 => null], $obj->y);
        assert\same('Z', $obj->z);
        assert\same(0, $obj->rw);
        assert\same(1, $obj->rw);
        $obj->rw = 3;
        assert\same(3, $obj->rw);
        assert\same(3, $obj->rw);
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

    /**
     * @covers ::property
     * @covers ::initProperties
     */
    public function testReadOnly(): void
    {
        $obj = new class
        {
            use PropertiesTrait\ReadOnly;

            protected function resolveA(): string
            {
                return 'A';
            }
        };
        $message = str('class %s has read-only access for magic-properties: a', get_class($obj));
        assert\exception($message, static function($obj) {$obj->a = '';}, $obj);
        assert\exception($message, static function($obj) {unset($obj->a);}, $obj);

        assert\true(isset($obj->a));
        assert\false(isset($obj->b));
        assert\same('A', $obj->a);
    }
}
