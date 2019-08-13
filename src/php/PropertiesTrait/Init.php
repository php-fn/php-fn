<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\PropertiesTrait;

use php;

/**
 * @mixin php\PropertiesTrait
 */
trait Init
{
    /**
     * @param iterable $properties
     */
    public function __construct(iterable $properties = [])
    {
        $this->propsInit($properties);
    }

    private function propsInit(iterable $properties = null): void
    {
        if (!defined('static::DEFAULT')) {
            $properties === null || $this->properties = php\merge($this->properties, $properties);
            return;
        }
        $defaults = constant('static::DEFAULT');
        $diff = [];
        $methods = php\merge($defaults, (array)$properties, function($value, $name) use ($defaults, &$diff) {
            $method = static::propMethod($name);
            if ($method || php\hasKey($name, $defaults)) {
                $this->properties[$name] = $value;
            } else {
                $diff[] = $name;
            }
            return $method ?: null;
        });

        $diff && php\fail\domain(
            'magic properties (%s) are not defined in %s::DEFAULT',
            implode(',', $diff),
            static::class
        );

        foreach ($methods as $name => $method) {
            $this->propGetter($name, $method, true);
        }
    }
}
