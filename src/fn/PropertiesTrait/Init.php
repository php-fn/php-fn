<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\PropertiesTrait;

use fn;

/**
 * @mixin fn\PropertiesTrait
 */
trait Init
{
    /**
     * @param iterable $properties
     */
    protected function initProperties(iterable $properties = null): void
    {
        if (!defined('static::DEFAULT')) {
            $properties === null || $this->properties = fn\merge($this->properties, $properties);
            return;
        }
        $defaults = constant('static::DEFAULT');
        $diff = [];
        $methods = fn\merge($defaults, (array)$properties, function($value, $name) use ($defaults, &$diff) {
            $method = static::propMethod($name);
            if ($method || fn\hasKey($name, $defaults)) {
                $this->properties[$name] = $value;
            } else {
                $diff[] = $name;
            }
            return $method ?: null;
        });

        $diff && fn\fail\domain(
            'magic properties (%s) are not defined in %s::DEFAULT',
            implode(',', $diff),
            static::class
        );

        foreach ($methods as $name => $method) {
            $this->propGetter($name, $method, true);
        }
    }
}
