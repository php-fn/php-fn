<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use ArrayIterator;
use Closure;
use EmptyIterator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use Php;
use RuntimeException;
use Traversable;

/**
 * @internal
 */
class Lazy implements Iterator
{
    protected const MAX_AGGREGATE = 10;

    use Php\IteratorTrait;

    /**
     * @param callable|iterable ...$proxy
     */
    public function __construct(...$proxy)
    {
        $this->iterState['args'] = $proxy;
    }

    protected function getInnerIterator(): Iterator
    {
        return $this->iterState['inner'] ?? $this->iterState['inner'] = static::unify(...$this->iterState['args']);
    }

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
            if ($counter++ > self::MAX_AGGREGATE) {
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
}
