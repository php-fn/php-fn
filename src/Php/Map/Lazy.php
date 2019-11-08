<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use ArrayIterator;
use Closure;
use EmptyIterator;
use Generator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use RuntimeException;
use Traversable;

/**
 * @internal
 */
class Lazy implements Iterator
{
    /**
     * @var int
     */
    protected const MAX_AGGREGATE = 10;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var Iterator
     */
    private $inner;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var bool
     */
    private $isReset;

    /**
     * @param callable|iterable ...$proxy
     */
    public function __construct(...$proxy)
    {
        $this->args = $proxy;
    }

    /**
     * @return Iterator
     */
    protected function getIterator(): Iterator
    {
        return $this->inner ?? $this->inner = static::unify(...$this->args);
    }

    /**
     * @param mixed ...$inner
     *
     * @return Iterator
     */
    protected static function unify(...$inner): Iterator
    {
        if (!$inner) {
            return new EmptyIterator;
        }
        $inner = $inner[0];

        if ($inner instanceof Closure) {
            $inner = $inner();
        }

        if (is_array($inner)) {
            return new ArrayIterator($inner);
        }

        $counter = 0;
        while ($inner instanceof IteratorAggregate) {
            if ($counter++ > 10) {
                throw new RuntimeException('$proxy::getIterator is too deep');
            }

            /** @var mixed $temp */
            if (($temp = $inner->getIterator()) === $inner) {
                throw new RuntimeException('Implementation $proxy::getIterator returns the same instance');
            }
            $inner = $temp;
        }

        if ($inner instanceof Traversable && !$inner instanceof Iterator) {
            return new IteratorIterator($inner);
        }

        if (!$inner instanceof Iterator) {
            throw new RuntimeException('Property $proxy must be iterable');
        }

        return $inner;
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    protected function get(string $property)
    {
        if (!$this->isReset) {
            $this->rewind();
        }
        return $this->cache[$property] ?? $this->getIterator()->$property();
    }

    /**
     * @return bool|null
     */
    public function isLast(): ?bool
    {
        if (!$this->isReset) {
            return null;
        }

        if (!$this->cache) {
            $this->cache = [
                'valid'   => $this->inner->valid(),
                'key'     => $this->inner->key(),
                'current' => $this->inner->current(),
            ];

            $this->inner->valid() && $this->inner->next();
        }
        return $this->cache['valid'] ? !$this->inner->valid() : null;
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        $this->isReset = true;
        $this->cache   = [];

        if ($this->inner instanceof Generator) {
            $this->inner = null;
        }
        $this->getIterator()->rewind();
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function next(): void
    {
        $this->cache ? $this->cache = [] : $this->getIterator()->next();
    }
}
