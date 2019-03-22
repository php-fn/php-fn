<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

/**
 */
trait PropertiesReadOnlyTrait
{
    use PropertiesReadWriteTrait;

    /**
     * @inheritdoc
     */
    public function __set(string $name, $value): void
    {
        fail('class %s has read-only access for magic-properties: %s', static::class, $name);
    }

    /**
     * @inheritdoc
     */
    public function __unset(string $name): void
    {
        fail('class %s has read-only access for magic-properties: %s', static::class, $name);
    }
}
