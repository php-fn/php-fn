<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use fn;

/**
 * @property-read mixed $value
 * @property-read mixed $key
 * @property-read mixed $group
 * @property-read iterable|callable $children
 */
class Value
{
    use fn\PropertiesTrait\ReadOnly;

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
        $this->properties['value'] = $value;
        return $this;
    }

    /**
     * @param mixed $key
     *
     * @return $this
     */
    public function andKey($key): self
    {
        $this->properties['key'] = $key;
        return $this;
    }

    /**
     * @param mixed $group
     *
     * @return $this
     */
    public function andGroup($group): self
    {
        $this->properties['group'] = $group;
        return $this;
    }

    /**
     * @param iterable|callable $children
     *
     * @return $this
     */
    public function andChildren($children): self
    {
        $this->properties['children'] = $children;
        return $this;
    }
}
