<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */
/** @noinspection PhpUndefinedClassConstantInspection */

namespace fn;

/**
 * @see http://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
 */
trait PropertiesReadWriteTrait
{
    /**
     * @var iterable
     */
    private $properties = [];

    /**
     * @param iterable $properties
     */
    private function initProperties(iterable $properties = null): void
    {
        if (defined('static::DEFAULT')) {
            $properties = $properties ?? [];
            if ($diff = array_diff(keys($properties), keys(static::DEFAULT))) {
                $diff = implode(',', $diff);
                fail\domain('magic properties (%s) are not defined in %s::DEFAULT', $diff, static::class);
            }
            $properties = merge(static::DEFAULT, $properties);
        }
        $properties === null || $this->properties = merge($this->properties, $properties);
    }

    /**
     * @param string $name
     * @param bool   $assert
     * @param mixed  ...$value
     *
     * @return $this|mixed
     */
    private function property(string $name, bool $assert, ...$value)
    {
        $has = hasKey($name, $this->properties);
        if ($assert) {
            $has || fail\domain('missing magic-property %s in %s', $name, static::class);
            if ($value) {
                $this->properties[$name] = $value[0];
                return $this;
            }
            return $this->properties[$name];
        }
        return $has;
    }

    /**
     * http://php.net/manual/language.oop5.overloading.php#object.isset
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->property($name, false);
    }

    /**
     * @see http://php.net/manual/language.oop5.overloading.php#object.get
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->property($name, true);
    }

    /**
     * @see http://php.net/manual/language.oop5.overloading.php#object.set
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set(string $name, $value): void
    {
        $this->property($name, true, $value);
    }

    /**
     * http://php.net/manual/language.oop5.overloading.php#object.unset
     *
     * @param string $name
     */
    public function __unset(string $name): void
    {
        $this->property($name, false) && $this->property($name, true, null);
    }
}
