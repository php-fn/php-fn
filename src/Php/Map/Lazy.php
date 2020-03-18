<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use Iterator;
use Php;

/**
 * @internal
 */
class Lazy implements Iterator
{
    use Php\IteratorTrait;

    /**
     * @param callable|iterable ...$args
     */
    public function __construct(...$args)
    {
        $this->iterState['args'] = $args;
    }

    protected function createInnerIterator(): Iterator
    {
        return Php::iter(...$this->iterState['args']);
    }
}
