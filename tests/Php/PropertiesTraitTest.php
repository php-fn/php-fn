<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use Countable;
use Php\Test\AssertTrait;
use PHPUnit\Framework\TestCase;

/**
 * @property $a
 * @property $rw
 * @property-read $x
 * @property-read $y
 * @property-read $z
 * @property-read $void
 * @property $string
 * @property $int
 * @property-read $float
 * @property-read $array
 * @property-read $iterable
 * @property-read $bool
 * @property-read $callable
 * @property-read $self
 * @property-read $access
 */
class PropertiesReadWrite
{
    use PropertiesTrait;
    use PropertiesTrait\Init;
    private const DEFAULT = ['a' => null];

    public function __construct($properties = [])
    {
        $this->properties = ['void' => 'void'];
        $this->propsInit($properties);
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
        return Php::mapValue([$count++ => $this->properties['y'] ?? null]);
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
        return $this->properties['_rw'] ?? Php::mapValue($count++);
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
     * @see $void
     */
    protected function resolveVoid(): void
    {
        Php::fail(__METHOD__);
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
        return Php::map();
    }
}

class PropertiesTraitTest extends TestCase
{
    use AssertTrait;

    public function testPropResolved(): void
    {
        $obj = new PropertiesReadWrite;

        self::assertSame('void', $obj->void);
        self::assertSame('', $obj->string);
        $obj->string = Php::map(['a', 'b']);
        self::assertSame("a\nb", $obj->string);

        self::assertSame(0, $obj->int);
        $obj->int = Php::map(['a', 'b']);
        self::assertSame(2, $obj->int);

        self::assertSame(.0, $obj->float);
        self::assertSame([], $obj->array);
        self::assertSame([], $obj->iterable);
        self::assertEquals(static function () {}, $obj->callable);
        self::assertSame($obj, $obj->self);
        self::assertEquals(Php::map(), $obj->access);
    }

    public function testTrait(): void
    {
        self::assertException(
            Php::str('magic properties (b,c) are not defined in %s::DEFAULT', PropertiesReadWrite::class),
            static function () {
                new PropertiesReadWrite(['b' => 'B', 'a' => 'A', 'c' => 'C', 'z' => 'Z']);
            }
        );

        self::assertAB('A', null, new class(['a' => 'A', 'b' => null])
        {
            use PropertiesTrait;
            use PropertiesTrait\Init;
        });

        self::assertAB('A', null, new class
        {
            use PropertiesTrait;
            use PropertiesTrait\Init;
            protected const DEFAULT = ['a' => 'A', 'b' => null];
        });

        self::assertAB('A', 'B', new class(['b' => 'B'])
        {
            use PropertiesTrait;
            use PropertiesTrait\Init;
            private const DEFAULT = ['a' => 'A', 'b' => null];
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
        self::assertSame('D', $obj->d);
        self::assertException(
            Php::str('class %s has read-only access for the magic-property: d', get_class($obj)),
            static function () use ($obj) {
                $obj->d = 'd';
            }
        );

        $obj = new class {
            use PropertiesTrait;

            private $unresolved = [];
            protected function propResolver(string $name, ...$args)
            {
                if ($args) {
                    $this->unresolved[$name] = $args;
                    return $args[0];
                }
                return $this->{self::propMethod($name)->name}(...$this->unresolved[$name] ?? []);
            }

            protected function resolveProp(...$args): array
            {
                return [$args[0] ?? 'A'];
            }
        };

        self::assertSame(['A'], $obj->prop);
        unset($obj->unknown, $obj->prop);
        self::assertSame(['A'], $obj->prop);
        $obj->prop = 'B';
        self::assertSame(['B'], $obj->prop);

        $obj = new PropertiesReadWrite(['x' => 'foo', 'z' => 'bar']);
        self::assertSame(['foo'], $obj->x);
        self::assertSame(['foo'], $obj->x);
        self::assertSame([null], $obj->y);
        self::assertSame([1 => null], $obj->y);
        self::assertSame('Z', $obj->z);
        self::assertSame(0, $obj->rw);
        self::assertSame(1, $obj->rw);
        $obj->rw = 3;
        self::assertSame(3, $obj->rw);
        self::assertSame(3, $obj->rw);
    }

    private static function assertAB($a, $b, $obj): void
    {
        self::assertTrue(isset($obj->a));
        self::assertTrue(isset($obj->b));
        self::assertFalse(isset($obj->c));
        self::assertSame($a, $obj->a);
        self::assertSame($b, $obj->b);
        self::assertException(
            Php::str('missing magic-property c in %s', get_class($obj)),
            static function () use ($obj) {
               $obj->c;
            }
         );
        unset($obj->a, $obj->b, $obj->c);
        self::assertTrue(isset($obj->a));
        self::assertTrue(isset($obj->b));
        self::assertFalse(isset($obj->c));
        self::assertNull($obj->a);
        self::assertNull($obj->b);
    }

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
        $message = Php::str('class %s has read-only access for magic-properties: a', get_class($obj));
        self::assertException($message, static function () use ($obj) {$obj->a = '';});
        self::assertException($message, static function () use ($obj) {unset($obj->a);});

        self::assertTrue(isset($obj->a));
        self::assertFalse(isset($obj->b));
        self::assertSame('A', $obj->a);
    }

    public function testNoMemoryLeak(): void
    {
        $correlation = Test\MemoryUsage::timeCorrelation(static function (callable $fill) {
            $obj = new PropertiesReadWrite();
            $obj->a = $fill(1024 * 100);
            return $obj;
        });
        self::assertLessThan(0.85, $correlation);
    }
}
