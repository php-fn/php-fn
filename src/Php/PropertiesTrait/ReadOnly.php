<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\PropertiesTrait;

use Php;

trait ReadOnly
{
    use Php\PropertiesTrait;

    /**
     * @inheritdoc
     */
    public function __set(string $name, $value): void
    {
        Php::fail('class %s has read-only access for magic-properties: %s', static::class, $name);
    }
}
