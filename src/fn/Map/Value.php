<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use fn;

/**
 * @internal
 *
 * @property-read mixed $value
 * @property-read mixed $key
 * @property-read mixed $group
 * @property-read iterable|callable $children
 */
class Value
{
    use fn\PropertiesReadOnlyTrait;

    /**
     * @param mixed [$value]
     * @param mixed [$key]
     * @param mixed [$group]
     * @param iterable|callable [$children]
     */
    public function __construct(...$args)
    {
        $this->properties = [
            'value'    => $args[0] ?? null,
            'key'      => $args[1] ?? null,
            'group'    => $args[2] ?? null,
            'children' => $args[3] ?? null,
        ];
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function andValue($value): self
    {
        return $this->property('value', true, $value);
    }

    /**
     * @param mixed $key
     *
     * @return $this
     */
    public function andKey($key): self
    {
        return $this->property('key', true, $key);
    }

    /**
     * @param mixed $group
     *
     * @return $this
     */
    public function andGroup($group): self
    {
        return $this->property('group', true, $group);
    }

    /**
     * @param iterable|callable $children
     *
     * @return $this
     */
    public function andChildren($children): self
    {
        return $this->property('children', true, $children);
    }
}
