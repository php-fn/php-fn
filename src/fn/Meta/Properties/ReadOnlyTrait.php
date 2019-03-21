<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Meta\Properties;

use fn;

/**
 */
trait ReadOnlyTrait
{
    use ReadWriteTrait;

    /**
     * @inheritdoc
     */
    public function __set($property, $value): void
    {
        fn\fail('class %s has read-only access for magic-properties: %s', static::class, $property);
    }

    /**
     * @inheritdoc
     */
    public function __unset($property): void
    {
        fn\fail('class %s has read-only access for magic-properties: %s', static::class, $property);
    }
}
