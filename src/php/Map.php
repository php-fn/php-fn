<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use Closure;
use Countable;
use IteratorAggregate;

/**
 * @property-read array  $keys
 * @property-read array  $traverse
 * @property-read array  $values
 * @property-read array  $tree
 * @property-read array  $leaves
 * @property-read array  $flatten
 * @property-read string $string
 * @property-read bool   $every
 * @property-read bool   $some
 */
class Map implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @uses data
     */
    use ArrayAccessTrait;

    /**
     * @var iterable
     */
    private $iterable;

    /**
     * @var callable
     */
    private $mappers;

    /**
     * @var Map\Tree
     */
    private $inner;

    /**
     * @param iterable $iterable
     * @param callable ...$mappers
     */
    public function __construct($iterable = null, callable ...$mappers)
    {
        $this->iterable = $iterable;
        $this->mappers  = $mappers;
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
            case 'keys':
                return Php::traverse($this->keys());
            case 'traverse':
                return is_array($this->data) ? $this->data : Php::traverse($this);
            case 'values':
                return Functions::toValues(is_array($this->data) ? $this->data : $this);
            case 'tree':
                return Functions::toValues($this->tree());
            case 'leaves':
                return Functions::toValues($this->leaves());
            case 'flatten':
                return Php::traverse($this->flatten());
            case 'string':
                return $this->string();
            case 'every':
                return $this->every(static function ($value) {
                    return $value;
                });
            case 'some':
                return $this->some(static function ($value) {
                    return $value;
                });
            default:
                Php::fail($property);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function __set($property, $value): void
    {
        Php::fail($property);
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function keys(callable ...$mappers): self
    {
        return $this->then(static function ($value, $key) {
            static $counter = 0;
            return Php::mapValue($key)->andKey($counter++);
        }, ...$mappers);
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function then(callable ...$mappers): self
    {
        return new static(
            is_array($this->data) ? $this->data : $this->iterable,
            ...$this->mappers, ...$mappers
        );
    }

    /**
     * @param callable $mapper
     *
     * @return static|\Traversable
     */
    public function tree(callable $mapper = null): self
    {
        return Functions::recursive($this, false, $mapper);
    }

    /**
     * @param callable $mapper
     *
     * @return static|iterable
     */
    public function leaves(callable $mapper = null): self
    {
        return Functions::recursive($this, true, $mapper);
    }

    /**
     * @param callable $mapper
     * @param string   $glue
     *
     * @return static
     */
    public function flatten(callable $mapper = null, string $glue = '/'): self
    {
        return $this->tree(static function (Map\Path $it, $value) use ($glue, $mapper) {
            $key = implode($glue, $it->keys);
            $mapper && $value = $mapper(...[$value, &$key, $it]);
            if ($value instanceof Map\Value) {
                return $value->key === null ? $value->andKey($key) : $value;
            }
            return $value === null ? $value : Php::mapKey($key)->andValue($value);
        });
    }

    /**
     * @param string|callable|array $delimiter
     *
     * @param array                 $replacements
     *
     * @return string
     */
    public function string($delimiter = PHP_EOL, ...$replacements): string
    {
        $string = '';
        if (!Php::isCallable($delimiter)) {
            if (is_array($delimiter)) {
                array_unshift($replacements, $delimiter);
                $delimiter = PHP_EOL;
            }
            $delimiter = static function ($counter) use ($delimiter) {
                return $counter ? $delimiter : '';
            };
        }
        Php::traverse($this->leaves(static function ($value, Map\Path $iterator) use ($delimiter, &$string) {
            static $counter = 0;
            $string .= $delimiter(...[$counter++, $iterator->getDepth(), $iterator]) . $value;

        }));
        return $replacements ? Php::str($string, ...$replacements) : $string;
    }

    /**
     * @param callable       $check
     * @param callable|mixed $true
     * @param callable|mixed $false
     *
     * @return bool|mixed
     */
    public function every(callable $check, $true = true, $false = false)
    {
        foreach ($this as $key => $value) {
            if (!$check($value, $key, $this)) {
                return Php::isCallable($false) ? $false($this) : $false;
            }
        }
        return Php::isCallable($true) ? $true($this) : $true;
    }

    /**
     * @param callable       $check
     * @param callable|mixed $true
     * @param callable|mixed $false
     *
     * @return bool|mixed
     */
    public function some(callable $check, $true = true, $false = false)
    {
        foreach ($this as $key => $value) {
            if ($check($value, $key, $this)) {
                return Php::isCallable($true) ? $true($this) : $true;
            }
        }
        return Php::isCallable($false) ? $false($this) : $false;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->string();
    }

    /**
     * @param string $property
     * @return bool
     */
    public function __isset($property): bool
    {
        return Php::hasValue($property, ['keys', 'traverse', 'map', 'values', 'tree', 'leaves', 'string', 'every', 'some']);
    }

    /**
     * @inheritdoc
     */
    public function __unset($property): void
    {
        Php::fail($property);
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return $this->getIterator()->count();
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Map\Tree
    {
        if (!$this->inner) {
            if (is_array($this->data)) {
                $this->inner = new Map\Tree($this->data);
            } else {
                $this->inner = new Map\Tree($this->iterable ?: [], ...$this->mappers);
            }
        }
        return $this->inner;
    }

    /**
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->getIterator()->isLast();
    }

    /**
     * @return array
     */
    private function data(): array
    {
        if (!is_array($this->data)) {
            $this->data    = Php::traverse($this);
            $this->inner   = null;
            $this->mappers = [];
        }
        return $this->data;
    }

    /**
     * @param callable|int $strategy callable or SORT_ constants
     * @param int          $flags    SORT_ constants
     * @return static
     */
    public function sort($strategy = null, $flags = null): self
    {
        return new static(new Map\Sort($this, $strategy, $flags));
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function values(callable ...$mappers): self
    {
        return $this->then(static function ($value) {
            static $counter = 0;
            return Php::mapValue($value)->andKey($counter++);
        }, ...$mappers);
    }

    /**
     * @param iterable ...$iterables
     * @return static
     */
    public function merge(...$iterables): self
    {
        return new static(new Map\Lazy(self::variadic('array_merge', $this, ...$iterables)));
    }

    /**
     * @param callable $function
     * @param array    ...$iterables
     * @return Closure
     */
    private static function variadic($function, ...$iterables): Closure
    {
        return static function () use ($function, $iterables) {
            return $function(...Php::traverse($iterables, static function ($iterable) {
                return Functions::toArray($iterable);
            }));
        };
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = 0): self
    {
        $from = max($offset, 0);
        $to   = $from + max($limit, 0);
        return $this->then(static function ($value) use ($from, $to) {
            static $count = 0;
            if ($count < $from) {
                $value = null;
            } else if ($to > 0 && $count >= $to) {
                $value = Php::mapBreak();
            }
            $count++;
            return $value;
        });
    }
}
