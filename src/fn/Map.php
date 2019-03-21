<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use ArrayAccess;
use Countable;
use fn\Map\Sort;
use IteratorAggregate;

/**
 * @property-read array $keys
 * @property-read array $traverse
 * @property-read array $values
 * @property-read array $tree
 * @property-read array $leaves
 * @property-read string $string
 * @property-read bool $every
 * @property-read bool $some
 */
class Map implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var iterable
     */
    private $iterable;

    /**
     * @var callable
     */
    private $mappers = [];

    /**
     * @var array
     */
    private $compiled;

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
        switch($property) {
            case 'keys':
                return traverse($this->keys());
            case 'traverse':
            case 'map':
                return is_array($this->compiled) ? $this->compiled : traverse($this);
            case 'values':
                return _\toValues(is_array($this->compiled) ? $this->compiled : $this);
            case 'tree':
                return _\toValues($this->tree());
            case 'leaves':
                return _\toValues($this->leaves());
            case 'string':
                return $this->string();
            case 'every':
                return $this->every(function($value) {return $value;});
            case 'some':
                return $this->some(function($value) {return $value;});
            default:
                fail\logic($property);
        }
        return null;
    }

    /**
     * @param string|callable|array $delimiter
     *
     * @param array $replacements
     *
     * @return string
     */
    public function string($delimiter = PHP_EOL, ...$replacements): string
    {
        $string = '';
        if (!isCallable($delimiter, true)) {
            if (is_array($delimiter)) {
                array_unshift($replacements, $delimiter);
                $delimiter = PHP_EOL;
            }
            $delimiter = function($counter) use($delimiter) {
                return $counter ? $delimiter : '';
            };
        }
        traverse($this->leaves(function($value, \RecursiveIteratorIterator $iterator) use ($delimiter, &$string) {
            static $counter = 0;
            $string .= $delimiter(...[$counter++, $iterator->getDepth(), $iterator]) . $value;

        }));
        return $replacements ? _\toString($string, ...$replacements) : $string;
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
        return hasValue($property, ['keys', 'traverse', 'map', 'values', 'tree', 'leaves', 'string', 'every', 'some']);
    }

    /**
     * @inheritdoc
     */
    public function __set($property, $value): void
    {
        fail\logic($property);
    }

    /**
     * @inheritdoc
     */
    public function __unset($property): void
    {
        fail\logic($property);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Traversable
    {
        if (!$this->inner) {
            if (is_array($this->compiled)) {
                $this->inner = new Map\Tree($this->compiled);
            } else {
                $this->inner = new Map\Tree($this->iterable ?: [], ...$this->mappers);
            }
        }
        return $this->inner;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return $this->getIterator()->count();
    }

    /**
     * @return bool
     */
    public function isLast()
    {
        return $this->getIterator()->isLast();
    }

    /**
     * @return array
     */
    private function compile(): array
    {
        if (!is_array($this->compiled)) {
            $this->compiled = traverse($this);
            $this->inner = null;
            $this->mappers = [];
        }
        return $this->compiled;
    }

    /**
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public function has($value, $strict = true): bool
    {
        return hasValue($value, $this->compile(), $strict);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset): bool
    {
        return hasKey($offset, $this->compile());
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        $this->offsetExists($offset) ?: fail\argument($offset);
        return $this->compiled[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->compile();
        $this->compiled[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset): void
    {
        $this->compile();
        unset($this->compiled[$offset]);
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function then(callable ...$mappers): self
    {
        return new static(
            is_array($this->compiled) ? $this->compiled : $this->iterable,
            ...$this->mappers, ...$mappers
        );
    }

    /**
     * @param callable|int $strategy callable or SORT_ constants
     * @param int $flags SORT_ constants
     * @return static
     */
    public function sort($strategy = null, $flags = null): self
    {
        return new static(new Sort($this, $strategy, $flags));
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function keys(callable ...$mappers): self
    {
        return $this->then(function ($value, $key)  {
            static $counter = 0;
            return mapValue($key)->andKey($counter++);
        }, ...$mappers);
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function values(callable ...$mappers): self
    {
        return $this->then(function ($value)  {
            static $counter = 0;
            return mapValue($value)->andKey($counter++);
        }, ...$mappers);
    }

    /**
     * @param callable $function
     * @param array ...$iterables
     * @return \Closure
     */
    private static function variadic($function, ...$iterables): \Closure
    {
        return function () use ($function, $iterables) {
            return $function(...traverse($iterables, function ($iterable) {
                return _\toArray($iterable);
            }));
        };
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
     * @param iterable ...$iterables
     * @return static
     */
    public function replace(...$iterables): self
    {
        return new static(new Map\Lazy(self::variadic('array_replace', $this, ...$iterables)));
    }

    /**
     * @param iterable ...$iterables
     * @return static
     */
    public function diff(...$iterables): self
    {
        return new static(new Map\Lazy(self::variadic('array_diff', $this, ...$iterables)));
    }

    /**
     * @param int $start
     * @param int $length
     * @return static
     */
    public function sub($start, $length = null): self
    {
        return new static(new Map\Lazy(function () use($start, $length) {
            return sub($this, $start, $length);
        }));
    }

    /**
     * @param mixed $needle
     * @param bool $strict
     * @return false|int|string
     */
    public function search($needle, $strict = true)
    {
        return array_search($needle, $this->compile(), $strict);
    }

    /**
     * @param callable $mapper
     *
     * @return static|\Traversable
     */
    public function tree(callable $mapper = null): self
    {
        return _\recursive($this, false, $mapper);
    }

    /**
     * @param callable $mapper
     *
     * @return static|\Traversable
     */
    public function leaves(callable $mapper = null): self
    {
        return _\recursive($this, true, $mapper);
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
                return isCallable($false, true) ? $false($this) : $false;
            }
        }
        return isCallable($true, true) ? $true($this) : $true;
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
                return isCallable($true, true) ? $true($this) : $true;
            }
        }
        return isCallable($false, true) ? $false($this) : $false;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = 0)
    {
        $from = max($offset, 0);
        $to   = $from + max($limit, 0);
        return $this->then(function($value) use($from, $to) {
            static $count = 0;
            if ($count < $from) {
                $value = null;
            } else if ($to > 0 && $count >= $to) {
                $value = mapBreak();
            }
            $count++;
            return $value;
        });
    }
}
