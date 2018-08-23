<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use ArrayAccess;
use Countable;
use fn\Map\Sort;
use IteratorAggregate;

/**
 * @mixin MapDeprecated
 *
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
    public function string($delimiter = PHP_EOL, ...$replacements)
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
    public function __toString()
    {
        return $this->string();
    }

    /**
     * @param string $property
     * @return bool
     */
    public function __isset($property)
    {
        return hasValue($property, ['keys', 'traverse', 'map', 'values', 'tree', 'leaves', 'string', 'every', 'some']);
    }

    /**
     * @inheritdoc
     */
    public function __set($property, $value)
    {
        fail\logic($property);
    }

    /**
     * @inheritdoc
     */
    public function __unset($property)
    {
        fail\logic($property);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
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
    public function count()
    {
        return $this->getIterator()->count();
    }

    /**
     * @return array
     */
    private function compile()
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
    public function has($value, $strict = true)
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
    public function offsetExists($offset)
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
    public function offsetSet($offset, $value)
    {
        $this->compile();
        $this->compiled[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->compile();
        unset($this->compiled[$offset]);
    }

    /**
     * @deprecated use method ::then instead
     */
    public function map(callable ...$mappers)
    {
        return $this->then(...$mappers);
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function then(callable ...$mappers)
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
    public function sort($strategy = null, $flags = null)
    {
        return new static(new Sort($this, $strategy, $flags));
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function keys(callable ...$mappers)
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
    public function values(callable ...$mappers)
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
    private static function variadic($function, ...$iterables)
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
    public function merge(...$iterables)
    {
        return new static(new Map\Lazy(self::variadic('array_merge', $this, ...$iterables)));
    }

    /**
     * @param iterable ...$iterables
     * @return static
     */
    public function replace(...$iterables)
    {
        return new static(new Map\Lazy(self::variadic('array_replace', $this, ...$iterables)));
    }

    /**
     * @param iterable ...$iterables
     * @return static
     */
    public function diff(...$iterables)
    {
        return new static(new Map\Lazy(self::variadic('array_diff', $this, ...$iterables)));
    }

    /**
     * @param int $start
     * @param int $length
     * @return static
     */
    public function sub($start, $length = null)
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
    public function tree(callable $mapper = null)
    {
        return _\recursive($this, false, $mapper);
    }

    /**
     * @param callable $mapper
     *
     * @return static|\Traversable
     */
    public function leaves(callable $mapper = null)
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
}
