<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */
/** @noinspection PhpUndefinedClassConstantInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace fn;

use fn\Map\Value;
use Generator;
use ReflectionMethod;

/**
 * @see https://php.net/manual/language.oop5.overloading.php#language.oop5.overloading.members
 */
trait PropertiesTrait
{
    /**
     * @var iterable
     */
    protected $properties = [];

    /**
     * @param string $name
     * @param bool $assertSetter
     * @param string $prefix = 'resolve'
     *
     * @return ReflectionMethod|false
     */
    protected function propertyMethod(string $name, bool $assertSetter = false, string $prefix = 'resolve')
    {
        static $methods = [];
        if (!isset($methods[$name][$prefix])) {
            $method = [static::class, "$prefix$name"];
            $methods[$name][$prefix] =  method_exists(...$method) ? new ReflectionMethod(...$method) : false;
        }
        if ($assertSetter && $setter = $methods[$name][$prefix]) {
            $setter->getNumberOfParameters() > 0 || fail\domain(
                'class %s has read-only access for the magic-property: %s',
                static::class,
                $name
            );
        }
        return $methods[$name][$prefix];
    }

    /**
     * @param string $name
     * @param mixed ...$args
     *
     * @return mixed
     */
    protected function propertyMethodInvoke(string $name, ...$args)
    {
        if ($args) {
            return $this->{$this->propertyMethod($name)->name}(...$args);
        }
        return $this->propertyGetterInvoke($name, $this->propertyMethod($name));
    }

    /**
     * @param $name
     * @param ReflectionMethod $method
     * @param bool $force
     * @return mixed|null
     */
    protected function propertyGetterInvoke(string $name, ReflectionMethod $method, bool $force = false)
    {
        if (!$force && hasKey($name, $this->properties)) {
            return $this->properties[$name];
        }
        foreach ($gen = $this->propertyGenerate($name, $this->{$method->name}()) as $property => $value) {
            $this->properties[$property] = $value;
        }
        return $this->properties[$name] ?? $gen->getReturn();
    }

    /**
     * @param string $property
     * @param $value
     *
     * @return Generator
     */
    protected function propertyGenerate(string $property, $value): Generator
    {
        if ($value instanceof Value) {
            return $value->value;
        }
        yield $property => $value;
    }

    /**
     * @param string $name
     * @param mixed  ...$args
     *
     * @return mixed
     */
    protected function property(string $name, ...$args)
    {
        $method = $this->propertyMethod($name);
        $has = hasKey($name, $this->properties);
        $has || $method || fail\domain('missing magic-property %s in %s', $name, static::class);

        if ($args) {
            return $this->propertyMethod($name, true) ?
                $this->propertyMethodInvoke($name, ...$args) :
                $this->properties[$name] = $args[0];
        }

        if ($has) {
            $value = $this->properties[$name];
            return $this->propertyResolved($value, $method) ? $value : $this->propertyGetterInvoke($name, $method, true);
        }
        return $this->propertyMethodInvoke($name);
    }

    /**
     * @param mixed $var
     * @param ReflectionMethod|null $method
     *
     * @return bool
     */
    protected function propertyResolved($var, $method = null): bool
    {
        if ($method && $method->hasReturnType()) {
            $type = $method->getReturnType()->getName();
            return $type === 'void' || type($var, $type === 'self' ? self::class : $type);
        }
        return true;
    }

    /**
     * https://php.net/manual/language.oop5.overloading.php#object.get
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->property($name);
    }

    /**
     * @see https://php.net/manual/language.oop5.overloading.php#object.set
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set(string $name, $value): void
    {
        $this->property($name, $value);
    }

    /**
     * @see https://php.net/manual/language.oop5.overloading.php#object.isset
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return hasKey($name, $this->properties) || $this->propertyMethod($name);
    }

    /**
     * @see https://php.net/manual/language.oop5.overloading.php#object.unset
     *
     * @param string $name
     */
    public function __unset(string $name): void
    {
        $this->__isset($name) && $this->__set($name, null);
    }
}
