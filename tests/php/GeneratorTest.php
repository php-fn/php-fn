<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

class GeneratorTest extends MapTest
{
    protected function map($iterable = null, ...$args): Map
    {
        return Php::map(static function () use ($iterable) {
            $iterable !== null && yield from $iterable;
        }, ...$args);
    }
}
