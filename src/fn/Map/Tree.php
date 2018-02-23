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
use IteratorAggregate;
use RecursiveIterator;

/**
 * Consolidates implementation of SPL array_* functions
 */
class Tree implements RecursiveIterator, Countable
{
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
     * @return Iterator
     */
    public function getInnerIterator()
    {
        if ($this->inner instanceof Iterator) {
            return $this->inner;
        }

        if (is_array($this->inner)) {
            return $this->inner = new ArrayIterator($this->inner);
        }

        if ($this->inner instanceof IteratorAggregate) {
            $this->inner = $this->inner->getIterator();
        }

        $this->inner instanceof Iterator ?: fn\fail('Property $inner must be iterable');
        return $this->inner;
    }

    /**
     * @return RecursiveIterator
     */
    private function getChildrenIterator()
    {
        if ($this->mapper && $this->doMap() && $this->children) {
            if (fn\isCallable($this->children, true)) {
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
        static $break, $null;
        if (!$break) {
            $break = fn\mapBreak();
            $null  = fn\mapNull();
        }
        if ($this->currentValid === $break) {
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

            if ($curValue === $break) {
                $this->validateInner($iter);
                $this->currentValid = $break;
                return false;
            }

            if ($curValue instanceof Value) {
                /**
                 * if there is at least one group instruction, we have to traverse the inner iterator completely
                 * from the current position (inclusive)
                 * @todo in this case the remaining children information is lost, fix it ASAP
                 */
                if ($curValue->group) {
                    $iter = $this->inner = new ArrayIterator(fn\traverse($iter, $this->mapper, false));
                    $iter->rewind();
                    $this->needsMap = true;
                    $this->mapper   = function() {
                        return new Value;
                    };
                    continue;
                }

                $curKey         = $curValue->key !== null ? $curValue->key : $curKey;
                $this->children = $curValue->children;
                $curValue       = $curValue->value !== null ? $curValue->value : $value;
            }

            $this->currentKey   = $curKey;
            $this->currentValue = $curValue === $null ? null : $curValue;
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
