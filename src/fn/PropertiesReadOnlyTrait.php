<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

/**
 * @deprecated
 */
trait PropertiesReadOnlyTrait
{
    use PropertiesTrait\ReadOnly;
    use PropertiesTrait\Init;

    /**
     * @inheritDoc
     */
    protected function propertyMethodInvoke(string $name)
    {
        return $this->{$this->propertyMethod($name)->name}();
    }
}
