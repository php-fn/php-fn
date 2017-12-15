<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use fn;
use InvalidArgumentException;

/**
 * @property-read mixed $value
 * @property-read mixed $key
 * @property-read mixed $group
 * @property-read iterable|callable $children
 */
class Value
{
    /**
     * @var string[]
     */
    const PROPERTIES = ['value', 'key', 'children', 'group'];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @param mixed [$value]
     * @param mixed [$key]
     * @param mixed [$group]
     * @param iterable|callable [$children]
     */
    public function __construct()
    {
        $args = func_get_args();
        fn\hasKey(0, $args) && $this->andValue($args[0]);
        fn\hasKey(1, $args) && $this->andKey($args[1]);
        fn\hasKey(2, $args) && $this->andGroup($args[2]);
        fn\hasKey(3, $args) && $this->andChildren($args[3]);
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function andValue($value)
    {
        $this->properties['value'] = $value;
        return $this;
    }

    /**
     * @param mixed $key
     *
     * @return $this
     */
    public function andKey($key)
    {
        $this->properties['key'] = $key;
        return $this;
    }

    /**
     * @param iterable|callable $children
     *
     * @return $this
     */
    public function andChildren($children)
    {
        $this->properties['children'] = $children;
        return $this;
    }

    /**
     * @param mixed $group
     *
     * @return $this
     */
    public function andGroup($group)
    {
        $this->properties['group'] = $group;
        return $this;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return fn\hasKey($property, $this->properties);
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (isset($this->properties[$property])) {
            return $this->properties[$property];
        }
        if (fn\hasValue($property, static::PROPERTIES)) {
            return null;
        }
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new InvalidArgumentException($property);
    }

    /**
     * @param string $property
     * @param string $value
     */
    public function __set($property, $value)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new InvalidArgumentException($property);
    }

    /**
     * @param string $property
     */
    public function __unset($property)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new InvalidArgumentException($property);
    }
}