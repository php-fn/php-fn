<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use Countable;
use fn;
use Iterator;
use RecursiveIterator;

/**
 * Consolidates implementation of SPL array_* functions
 */
class Tree implements RecursiveIterator, Countable
{
    /**
     * @var callable[]
     */
    protected $mappers;

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
     * @param callable ...$mappers
     */
    public function __construct($inner, callable ...$mappers)
    {
        $this->inner   = $inner;
        $this->mappers = $mappers;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return iterator_count($this);
    }

    /**
     * @return Inner
     */
    public function getInnerIterator(): Inner
    {
        if (!$this->inner instanceof Inner) {
            $this->inner = new Inner($this->inner);
        }
        return $this->inner;
    }

    /**
     * @return RecursiveIterator
     */
    private function getChildrenIterator(): ?RecursiveIterator
    {
        if ($this->mappers && $this->doMap() && $this->children) {
            if (fn\isCallable($this->children)) {
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
    public function isLast(): bool
    {
        return $this->getInnerIterator()->isLast();
    }

    /**
     * @return bool
     */
    private function doMap(): bool
    {
        static $break, $null;
        if (!$break) {
            $break = fn\mapBreak();
            $null = fn\mapNull();
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

            foreach ($this->mappers as $mapper) {
                $curValue = call_user_func_array($mapper, [$curValue === $null ? null : $curValue, &$curKey, $this]);
                if ($curValue === null) {
                    $this->needsNext = true;
                    continue 2;
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
                        $iter = $this->inner = new Inner(fn\traverse($iter, $mapper, false));
                        $iter->rewind();
                        $this->needsMap = true;
                        $this->mappers = [function() {
                            return new Value;
                        }];
                        continue;
                    }
                    // @todo only children of the last value are handled
                    $this->children = $curValue->children;

                    $curKey = $curValue->key !== null ? $curValue->key : $curKey;
                    $curValue = $curValue->value !== null ? $curValue->value : $value;

                }

            }
            $this->currentKey = $curKey;
            $this->currentValue = $curValue === $null ? null : $curValue;            break;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        if ($this->mappers) {
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
    private function validateInner(Iterator $inner): bool
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
        if ($this->mappers) {
            return $this->doMap() ? $this->currentValue : null;
        }
        return $this->getInnerIterator()->current();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        if ($this->mappers) {
            return $this->doMap() ? $this->currentKey : null;
        }
        return $this->getInnerIterator()->key();
    }

    /**
     * @inheritdoc
     */
    public function hasChildren(): bool
    {
        if ($this->getChildrenIterator()) {
            return true;
        }
        $inner = $this->getInnerIterator();
        return $inner->valid() && is_iterable($inner->current());
    }

    /**
     * @inheritdoc
     */
    public function getChildren(): RecursiveIterator
    {
        if ($childrenIterator = $this->getChildrenIterator()) {
            return $childrenIterator;
        }
        $inner = $this->getInnerIterator();
        if ($inner->valid() && is_iterable($current = $inner->current())) {
            return new static($current);
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
    public function next(): void
    {
        if ($this->mappers) {
            $this->needsNext = false;
            $this->needsMap = true;
        }
        $this->getInnerIterator()->next();
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        if ($this->mappers) {
            return $this->doMap();
        }
        return $this->getInnerIterator()->valid();
    }

    /**
     * @param callable $mapper
     *
     * @return static|\Traversable
     */
    public function recursive(callable $mapper = null): self
    {
        return fn\_\recursive($this, false, $mapper);
    }

    /**
     * @param callable $mapper
     *
     * @return static|\Traversable
     */
    public function flatten(callable $mapper = null): self
    {
        return fn\_\recursive($this, true, $mapper);
    }
}
