<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use Countable;
use IteratorAggregate;

/**
 */
class Map implements IteratorAggregate, Countable
{
    /**
     * @var Map\Tree
     */
    private $inner;

    /**
     * @param iterable $iterable
     * @param callable $mapper
     */
    public function __construct($iterable = null, callable $mapper = null)
    {
        $this->inner = new Map\Tree($iterable ?: [], $mapper);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return $this->inner;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return $this->inner->count();
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
        return (new static($this->inner, function($value, $key) use(&$counter) {
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
