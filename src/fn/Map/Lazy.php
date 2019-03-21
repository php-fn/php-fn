<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use ArrayIterator;
use IteratorAggregate;

/**
 */
class Lazy implements IteratorAggregate
{
    /**
     * @var callable
     */
    protected $factory;

    /**
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): ?\Traversable
    {
        $iter = call_user_func($this->factory);
        return is_array($iter) ? new ArrayIterator($iter) : $iter;
    }
}
