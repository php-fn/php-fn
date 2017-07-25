<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use fn;

/**
 * Consolidates implementation of SPL array_* functions
 */
class Tree implements \OuterIterator, \RecursiveIterator, \Countable
{
    private $needsRewind = true;
    private $needsNext = true;
    private $needsMap = true;

    private $valid = null;
    private $key = null;
    private $current = null;
    private $children = null;

    /**
     * @var iterable|\Traversable
     */
    protected $inner;

    /**
     * @var callable
     */
    protected $mapper;

    /**
     * @param iterable|\Traversable $inner
     * @param callable|null $mapper
     */
    public function __construct($inner, callable $mapper = null)
    {
        $this->inner = $inner;
        $this->mapper = $mapper;
    }

    /**
     * @inheritdoc
     */
    public function getInnerIterator()
    {
        if ($this->inner instanceof \Iterator) {
            return $this->inner;
        }

        if (is_array($this->inner)) {
            return $this->inner = new \ArrayIterator($this->inner);
        }

        if (!$this->inner instanceof \Iterator && $this->inner instanceof \Traversable) {
            return $this->inner = new \IteratorIterator($this->inner);
        }

        throw new \RuntimeException('Property $inner must be iterable');
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return iterator_count($this);
    }

    /**
     * @return \RecursiveIterator
     */
    private function children()
    {
        if ($this->mapper && $this->doMap() && $this->children) {
            if (is_callable($this->children)) {
                $this->children = call_user_func($this->children, $this->current(), $this->key(), $this);
            }
            if (!$this->children instanceof \RecursiveIterator) {
                $this->children = new static($this->children);
            }
            return $this->children;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function hasChildren()
    {
        $inner = $this->getInnerIterator();
        if ($inner instanceof \RecursiveIterator) {
            return $inner->hasChildren();
        }
        return $this->children() && true;
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        $inner = $this->getInnerIterator();
        if ($inner instanceof \RecursiveIterator) {
            return $inner->getChildren();
        }
        if ($children = $this->children()) {
            return $children;
        }
        static $empty;
        if (!$empty) {
            $empty = new static([]);
        }
        return $empty;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        if ($this->mapper) {
            $this->needsRewind = false;
            $this->needsNext = false;
            $this->needsMap = true;

            $this->valid = null;
            $this->key = null;
            $this->current = null;
            $this->children = null;

        }
        $this->getInnerIterator()->rewind();
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        if ($this->mapper) {
            $this->needsNext = false;
            $this->needsMap = true;
        }
        $this->getInnerIterator()->next();
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        if ($this->mapper) {
            return $this->doMap();
        }
        return $this->getInnerIterator()->valid();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        if ($this->mapper) {
            return $this->doMap() ? $this->key : null;
        }
        return $this->getInnerIterator()->key();
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        if ($this->mapper) {
            return $this->doMap() ? $this->current : null;
        }
        return $this->getInnerIterator()->current();
    }

    /**
     * @return bool
     */
    private function doMap()
    {
        static $stop;
        if (!$stop) {
            $stop = fn\map\stop();
        }
        if ($this->valid === $stop) {
            return false;
        }
        $this->needsRewind && $this->rewind();

        if (!$this->needsMap) {
            return $this->valid;
        }

        $inner = $this->getInnerIterator();
        while (true) {
            if (!$this->validateInner($inner)) {
                return false;
            }
            if ($this->needsNext) {
                $this->needsNext = false;
                $this->needsMap = true;
                $inner->next();
                if (!$this->validateInner($inner)) {
                    return false;
                }
            }
            if (!$this->needsMap) {
                continue;
            }
            $this->needsMap = false;

            $value = $current = $inner->current();
            $key = $inner->key();

            $current = call_user_func_array($this->mapper, [$current, &$key, $this]);

            if ($current === null) {
                $this->needsNext = true;
                continue;
            }

            if ($current === $stop) {
                $this->validateInner($inner);
                $this->valid = $stop;
                return false;
            }

            if ($current instanceof Value) {
                $key = isset($current->key) ? $current->key : $key;
                $this->children = isset($current->children) ? $current->children : null;
                $current = isset($current->value) ? $current->value : $value;
            }

            $this->key = $key;
            $this->current = $current;
            break;
        }
        return true;
    }

    /**
     * @param \Iterator $inner
     *
     * @return bool
     */
    private function validateInner(\Iterator $inner)
    {
        if (!($this->valid = $inner->valid())) {
            $this->key = null;
            $this->current = null;
            $this->children = null;
        }
        return $this->valid;
    }
}