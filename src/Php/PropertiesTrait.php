<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */
/** @noinspection PhpUndefinedClassConstantInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace Php;

use Php\Map\Value;
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
    private static function propMethod(string $name, bool $assertSetter = false, string $prefix = 'resolve')
    {
        static $methods = [];
        if (!isset($methods[$name][$prefix])) {
            $method = [static::class, "$prefix$name"];
            $methods[$name][$prefix] =  method_exists(...$method) ? new ReflectionMethod(...$method) : false;
        }
        if ($assertSetter && $setter = $methods[$name][$prefix]) {
            $setter->getNumberOfParameters() > 0 || Php::fail(
                'class %s has read-only access for the magic-property: %s', static::class, $name
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
    private function propResolver(string $name, ...$args)
    {
        if ($args) {
            return $this->{static::propMethod($name)->name}(...$args);
        }
        return $this->propGetter($name, static::propMethod($name));
    }

    /**
     * @param $name
     * @param ReflectionMethod $method
     * @param bool $force
     * @return mixed|null
     */
    private function propGetter(string $name, ReflectionMethod $method, bool $force = false)
    {
        if (!$force && Php::hasKey($name, $this->properties)) {
            return $this->properties[$name];
        }
        if (($value = $this->{$method->name}()) instanceof Value) {
            return $value->value;
        }

        return $this->properties[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed  ...$args
     *
     * @return mixed
     */
    private function prop(string $name, ...$args)
    {
        $method = static::propMethod($name);
        $has = Php::hasKey($name, $this->properties);
        $has || $method || Php::fail('missing magic-property %s in %s', $name, static::class);

        if ($args) {
            return static::propMethod($name, true) ?
                $this->propResolver($name, ...$args) :
                $this->properties[$name] = $args[0];
        }

        if ($has) {
            $value = $this->properties[$name];
            return self::propResolved($value, $method) ? $value : $this->propGetter($name, $method, true);
        }
        return $this->propResolver($name);
    }

    /**
     * @param mixed $var
     * @param ReflectionMethod|null $method
     *
     * @return bool
     */
    private static function propResolved($var, $method = null): bool
    {
        if ($method && $method->hasReturnType()) {
            $type = $method->getReturnType()->getName();
            return $type === 'void' || Php::type($var, $type === 'self' ? self::class : $type);
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
        return $this->prop($name);
    }

    /**
     * @see https://php.net/manual/language.oop5.overloading.php#object.set
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set(string $name, $value): void
    {
        $this->prop($name, $value);
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
        return Php::hasKey($name, $this->properties) || static::propMethod($name);
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
