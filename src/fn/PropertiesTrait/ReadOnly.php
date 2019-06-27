<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\PropertiesTrait;

use fn;

/**
 */
trait ReadOnly
{
    use fn\PropertiesTrait;

    /**
     * @inheritdoc
     */
    public function __set(string $name, $value): void
    {
        fn\fail('class %s has read-only access for magic-properties: %s', static::class, $name);
    }
}
