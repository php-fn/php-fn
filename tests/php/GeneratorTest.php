<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

/**
 * @coversDefaultClass Map
 */
class GeneratorTest extends MapTest
{
    /**
     * @inheritDoc
     */
    protected function map($iterable = null, ...$args): Map
    {
        return map(static function() use ($iterable) {
            $iterable !== null && yield from $iterable;
        }, ...$args);
    }
}
