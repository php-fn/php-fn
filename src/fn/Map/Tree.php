<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use ArrayIterator;
use Countable;
use fn;
use Iterator;
use IteratorIterator;
use OuterIterator;
use RecursiveIterator;
use RuntimeException;
use Traversable;

/**
 * Consolidates implementation of SPL array_* functions
 */
class Tree implements OuterIterator, RecursiveIterator, Countable
{
    /**
     * @var iterable|Traversable|callable
     */
    protected $data;

    /**
     * @var callable
     */
    protected $mapper;

    private $inner;
    private $needsRewind = true;
    private $needsNext = true;
    private $needsMap = true;
    private $currentValid;
    private $currentKey;
    private $currentValue;
    private $children;

    /**
     * @param iterable|Traversable|callable $data
     * @param callable|null $mapper
     */
    public function __construct($data, callable $mapper = null)
    {
        $this->data = $data;
        $this->mapper = $mapper;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return iterator_count($this);
    }

    /**
     * @inheritdoc
     */
    public function hasChildren()
    {
        $iter = $this->getInnerIterator();
        return ($iter instanceof RecursiveIterator && $iter->hasChildren()) || $this->getChildrenIterator();
    }

    /**
     * @inheritdoc
     * @throws RuntimeException
     */
    public function getInnerIterator()
    {
        if ($this->inner) {
            return $this->inner;
        }
        $iter = is_callable($this->data) ? call_user_func($this->data) : $this->data;
        if ($iter instanceof Iterator) {
            $this->inner = $iter;
        } else if (is_array($iter)) {
            $this->inner = new ArrayIterator($iter);
        } else if (!$iter instanceof Iterator && $iter instanceof Traversable) {
            $this->inner = new IteratorIterator($iter);
        } else {
            throw new RuntimeException('Property $data must be iterable');
        }
        return $this->inner;
    }

    /**
     * @return RecursiveIterator
     */
    private function getChildrenIterator()
    {
        if ($this->mapper && $this->doMap() && $this->children) {
            if (is_callable($this->children)) {
                $this->children = call_user_func($this->children, $this->current(), $this->key(), $this);
            }
            if (!$this->children instanceof RecursiveIterator) {
                $this->children = new static($this->children);
            }
            return $this->children;
        }
        return null;
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
        if ($this->currentValid === $stop) {
            return false;
        }
        $this->needsRewind && $this->rewind();

        if (!$this->needsMap) {
            return $this->currentValid;
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
                $this->currentValid = $stop;
                return false;
            }

            if ($curValue instanceof Value) {
                $curKey = isset($curValue->key) ? $curValue->key : $curKey;
                $this->children = isset($curValue->children) ? $curValue->children : null;
                $curValue = isset($curValue->value) ? $curValue->value : $value;
            }

            $this->currentKey = $curKey;
            $this->currentValue = $curValue;
            break;
        }
        return true;
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

            $this->currentValid = null;
            $this->currentKey = null;
            $this->currentValue = null;
            $this->children = null;

        }
        $this->getInnerIterator()->rewind();
    }

    /**
     * @param Iterator $inner
     *
     * @return bool
     */
    private function validateInner(Iterator $inner)
    {
        if (!($this->currentValid = $inner->valid())) {
            $this->currentKey = null;
            $this->currentValue = null;
            $this->children = null;
        }
        return $this->currentValid;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        if ($this->mapper) {
            return $this->doMap() ? $this->currentValue : null;
        }
        return $this->getInnerIterator()->current();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        if ($this->mapper) {
            return $this->doMap() ? $this->currentKey : null;
        }
        return $this->getInnerIterator()->key();
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        $iter = $this->getInnerIterator();
        if ($iter instanceof RecursiveIterator) {
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
}
