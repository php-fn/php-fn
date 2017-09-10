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

    private $valid;
    private $key;
    private $current;
    private $children;

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
     * @throws \RuntimeException
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
    private function getChildrenIterator()
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
        $iter = $this->getInnerIterator();
        return ($iter instanceof \RecursiveIterator && $iter->hasChildren()) || $this->getChildrenIterator();
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        $iter = $this->getInnerIterator();
        if ($iter instanceof \RecursiveIterator) {
            return $iter->getChildren();
        }
        if ($childrenIterator = $this->getChildrenIterator()) {
            return $childrenIterator;
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

        $iter = $this->getInnerIterator();
        while (true) {
            if (!$this->validateInner($iter)) {
                return false;
            }
            if ($this->needsNext) {
                $this->needsNext = false;
                $this->needsMap = true;
                $iter->next();
                if (!$this->validateInner($iter)) {
                    return false;
                }
            }
            if (!$this->needsMap) {
                continue;
            }
            $this->needsMap = false;

            $value = $curValue = $iter->current();
            $curKey = $iter->key();

            $curValue = call_user_func_array($this->mapper, [$curValue, &$curKey, $this]);

            if ($curValue === null) {
                $this->needsNext = true;
                continue;
            }

            if ($curValue === $stop) {
                $this->validateInner($iter);
                $this->valid = $stop;
                return false;
            }

            if ($curValue instanceof Value) {
                $curKey = isset($curValue->key) ? $curValue->key : $curKey;
                $this->children = isset($curValue->children) ? $curValue->children : null;
                $curValue = isset($curValue->value) ? $curValue->value : $value;
            }

            $this->key = $curKey;
            $this->current = $curValue;
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