<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;


/**
 * @deprecated
 */
trait PropertiesReadWriteTrait
{
    use PropertiesTrait;
    use PropertiesTrait\Init;

    /**
     * @inheritDoc
     */
    protected function propertyMethodInvoke(string $name, ...$args)
    {
        return $this->{$this->propertyMethod($name)->name}(...$args);
    }
}
