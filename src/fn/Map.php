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
use IteratorAggregate;
use LogicException;

/**
 * @property-read array $keys
 * @property-read array $map
 * @property-read array $values
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
     * @return array
     * @throws LogicException
     */
    public function __get($property)
    {
        switch($property) {
            case 'keys':
                return traverse($this->keys());
            case 'map':
                return $this();
            case 'values':
                return toValues($this());
            default:
                throw new LogicException($property);
        }
    }

    /**
     * @param string $property
     * @return bool
     */
    public function __isset($property)
    {
        return in($property, ['keys', 'map', 'values']);
    }

    /**
     * @param string $property
     * @param mixed $value
     * @throws LogicException
     */
    public function __set($property, $value)
    {
        throw new LogicException($property);
    }

    /**
     * @param string $property
     * @throws LogicException
     */
    public function __unset($property)
    {
        throw new LogicException($property);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return $this->data ? new Map\Tree($this->data) : $this->iter;
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
    public function __invoke()
    {
        return $this->data ?: $this->data = traverse($this);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return is($offset, $this());
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->data[$offset];
        }
        throw new \InvalidArgumentException($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this();
        $this->data[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this();
        unset($this->data[$offset]);
    }

    /**
     * @param callable[] ...$mappers
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
     * @param callable[] ...$mappers
     * @return static
     */
    public function keys(callable ...$mappers)
    {
        $counter = 0;
        return (new static($this->getIterator(), function ($value, $key) use (&$counter) {
            return mapValue($key)->andKey($counter++);
        }))->map(...$mappers);
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
                return traverse($iterable);
            }));
        };
    }

    /**
     * @param iterable[] ...$iterables
     * @return static
     */
    public function merge(...$iterables)
    {
        return new static(new Map\Lazy(self::variadic('array_merge', $this, ...$iterables)));
    }

    /**
     * @param iterable[] ...$iterables
     * @return static
     */
    public function replace(...$iterables)
    {
        return new static(new Map\Lazy(self::variadic('array_replace', $this, ...$iterables)));
    }

    /**
     * @param iterable[] ...$iterables
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
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public function has($value, $strict = true)
    {
        return in($value, $this(), $strict);
    }

    /**
     * @param mixed $needle
     * @param bool $strict
     * @return false|int|string
     */
    public function search($needle, $strict = true)
    {
        return array_search($needle, $this(), $strict);
    }
}
