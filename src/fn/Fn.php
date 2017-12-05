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

/**
 */
class Fn implements IteratorAggregate, Countable, ArrayAccess
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
     * @param iterable $iterable
     * @param callable $mapper
     */
    public function __construct($iterable = null, callable $mapper = null)
    {
        $this->iter = new Map\Tree($iterable ?: [], $mapper);
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
        return $this->data ?: $this->data = map($this);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        $this();
        return isset($this->data[$offset]) || array_key_exists($offset, $this->data);
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
        return (new static($this->getIterator(), function($value, $key) use(&$counter) {
            return map\value($key)->andKey($counter++);
        }))->map(...$mappers);
    }

    /**
     * @param callable $function
     * @param array ...$iterables
     * @return static
     */
    private function variadic($function, ...$iterables)
    {
        return new static(function() use($function, $iterables) {
            return $function(map($this), ...map($iterables, function($iterable) {
                return map($iterable);
            }));
        });
    }

    /**
     * @param iterable[] ...$iterables
     * @return static
     */
    public function merge(...$iterables)
    {
        return $this->variadic('array_merge', ...$iterables);
    }

    /**
     * @param iterable[] ...$iterables
     * @return static
     */
    public function replace(...$iterables)
    {
        return $this->variadic('array_replace', ...$iterables);
    }

    /**
     * @param iterable[] ...$iterables
     * @return static
     */
    public function diff(...$iterables)
    {
        return $this->variadic('array_diff', ...$iterables);
    }
}