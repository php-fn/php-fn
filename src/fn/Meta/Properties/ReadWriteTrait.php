<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Meta\Properties;

use fn;

/**
 * @property array $properties
 */
trait ReadWriteTrait
{
    /**
     * @param array $properties
     */
    public function __construct(array $properties = null)
    {
        property_exists($this, 'properties') ?: fn\fail('missing property $properties in %s', static::class);
        if ($properties !== null) {
            if ($diff = implode(', ', array_diff(array_keys($properties), array_keys($this->properties)))) {
                fn\fail\value($diff);
            }
            $this->properties = array_replace($this->properties, $properties);
        }
    }

    /**
     * @param string $name
     */
    private function assertProperty($name): void
    {
        fn\hasKey($name, $this->properties) ?: fn\fail\bounds('missing magic-property %s in %s', $name, static::class);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name): bool
    {
        return fn\hasKey($name, $this->properties);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $this->assertProperty($name);
        return $this->properties[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value): void
    {
        $this->assertProperty($name);
        $this->properties[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function __unset($name): void
    {
        $this->__set($name, null);
    }
}
