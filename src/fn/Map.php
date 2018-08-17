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
     * @var Map\Tree
     */
    private $iter;

    /**
     * @var array
     */
    private $data;

    /**
     * @param iterable ...$iterable
     * @param callable $mapper
     */
    public function __construct($iterable = null, callable $mapper = null)
    {
        $this->iter = new Map\Tree($iterable ?: [], $mapper);
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
                return is_array($this->data) ? $this->data : traverse($this);
            case 'values':
                return _\toValues(is_array($this->data) ? $this->data : $this);
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
            $delimiter = function($counter, $depth, \RecursiveIteratorIterator $iterator) use($delimiter) {
                return $counter ? $delimiter : '';
            };
        }
        traverse($this->leaves(function($value, \RecursiveIteratorIterator $iterator) use ($delimiter, &$string) {
            static $counter = 0;
            $string .= $delimiter($counter++, $iterator->getDepth(), $iterator) . $value;

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
        return is_array($this->data) ? new Map\Tree($this->data) : $this->iter;
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
    private function getData()
    {
        return is_array($this->data) ? $this->data : $this->data = traverse($this);
    }

    /**
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public function has($value, $strict = true)
    {
        return hasValue($value, $this->getData(), $strict);
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
        return hasKey($offset, $this->getData());
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        $this->offsetExists($offset) ?: fail\argument($offset);
        return $this->data[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->getData();
        $this->data[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->getData();
        unset($this->data[$offset]);
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function map(callable ...$mappers)
    {
        if (!$mappers) {
            return $this;
        }
        $new = $this;
        foreach ($mappers as $mapper) {
            $new = new static($new, $mapper);
        }
        return $new;
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
        $counter = 0;
        $mapped = new static($this->getIterator(), function ($value, $key) use (&$counter) {
            return mapValue($key)->andKey($counter++);
        });
        return $mappers ? $mapped->map(...$mappers) : $mapped;
    }

    /**
     * @param callable ...$mappers
     * @return static
     */
    public function values(callable ...$mappers)
    {
        $counter = 0;
        $mapped = new static($this->getIterator(), function ($value) use (&$counter) {
            return mapValue($value)->andKey($counter++);
        });
        return $mappers ? $mapped->map(...$mappers) : $mapped;
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
        return array_search($needle, $this->getData(), $strict);
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
