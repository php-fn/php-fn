<?php declare(strict_types=1);
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
}
