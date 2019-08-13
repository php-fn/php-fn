<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\PropertiesTrait;

use php;

/**
 */
trait ReadOnly
{
    use php\PropertiesTrait;

    /**
     * @inheritdoc
     */
    public function __set(string $name, $value): void
    {
        php\fail('class %s has read-only access for magic-properties: %s', static::class, $name);
    }
}
