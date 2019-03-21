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
    use fn\Meta\Properties\ReadOnlyTrait;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @param mixed [$value]
     * @param mixed [$key]
     * @param mixed [$group]
     * @param iterable|callable [$children]
     */
    public function __construct(...$args)
    {
        $this->properties = [
            'value'    => fn\hasKey(0, $args) ? $args[0] : null,
            'key'      => fn\hasKey(1, $args) ? $args[1] : null,
            'group'    => fn\hasKey(2, $args) ? $args[2] : null,
            'children' => fn\hasKey(3, $args) ? $args[3] : null,
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
