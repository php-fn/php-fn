<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */
/** @noinspection PhpUndefinedClassConstantInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace fn;

use ReflectionMethod;

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
     *
     * @return ReflectionMethod|false
     */
    private function propertyMethod(string $name)
    {
        static $methods = [];
        if (!isset($methods[$name])) {
            $method = [static::class, "resolve$name"];
            $methods[$name] =  method_exists(...$method) ? new ReflectionMethod(...$method) : false;
        }
        return $methods[$name];
    }

    /**
     * @param string $name
     * @param mixed ...$args
     *
     * @return mixed
     */
    private function propertyMethodInvoke(string $name, ...$args)
    {
        return $this->{$this->propertyMethod($name)->name}(...$args);
    }

    /**
     * @param string $name
     * @param bool   $assert
     * @param mixed  ...$args
     *
     * @return $this|mixed
     */
    private function property(string $name, bool $assert, ...$args)
    {
        $has = hasKey($name, $this->properties);

        if ($assert) {
            if (!($has || $this->propertyMethod($name))) {
                fail\domain('missing magic-property %s in %s', $name, static::class);
            }
            if ($args) {
                if ($method = $this->propertyMethod($name)) {

                    $method->getNumberOfParameters() > 0 || fail\domain(
                        'class %s has read-only access for the magic-property: %s',
                        static::class,
                        $name
                    );

                    $this->propertyMethodInvoke($name, ...$args);
                } else {
                    $this->properties[$name] = $args[0];
                }
                return $this;
            }
            return $has ? $this->properties[$name] : $this->propertyMethodInvoke($name);
        }

        return $has || $this->propertyMethod($name);
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
