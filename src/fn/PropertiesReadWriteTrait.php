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
    protected $properties = [];

    private function traitConfig(string $prop): array
    {
        static $cache = [];
        if (!isset($cache[static::class])) {
            $static = defined('static::TRAIT_PROPERTIES') ? static::TRAIT_PROPERTIES : [];
            $self = defined('self::TRAIT_PROPERTIES') ? self::TRAIT_PROPERTIES : [];

            $cache[static::class] = [
                'resolve' => array_unique([$self['resolve'] ?? null, $static['resolve'] ?? null, 'resolve*']),
                'compile' => array_unique([$self['compile'] ?? null, $static['compile'] ?? null, 'compile*']),
                'defaults' => ($self['defaults'] ?? []) + ($static['defaults'] ?? []),
            ];
        }
        return $cache[static::class][$prop];
    }

    /**
     * @param $properties
     */
    private function initProperties($properties = null): void
    {
        if ($defaults = $this->traitConfig('defaults')) {
            $properties = $properties ?? [];
            if ($diff = array_diff(keys($properties), keys($defaults))) {
                $diff = implode(',', $diff);
                fail\domain(
                    "magic properties (%s) are not defined in %s::TRAIT_PROPERTIES['defaults']",
                    $diff,
                    static::class
                );
            }
            $properties = merge($defaults, $properties);
        }
        $properties === null || $this->properties = merge($this->properties, $properties);
    }

    /**
     * @param string $name
     * @return array
     */
    private function propertyMethod(string $name): array
    {
        static $cache = [];
        if (isset($cache[static::class][$name])) {
            return $cache[static::class][$name];
        }
        $cache[static::class][$name] = [];
        foreach (['resolve' => false, 'compile' => true] as $prop => $callOnce) {
            foreach ($this->traitConfig($prop) as $prefix) {
                if (method_exists(...$method = [static::class, str_replace('*', $name, $prefix)])) {
                    $cache[static::class][$name] = [new ReflectionMethod(...$method), $callOnce];
                }
            }
        }
        return $cache[static::class][$name];
    }

    /**
     * @param string $name
     * @param bool   $assert
     * @param mixed  ...$args
     *
     * @return mixed
     */
    private function property(string $name, bool $assert, ...$args)
    {
        if (hasKey($name, $this->properties)) {
            if (!$assert) {
                return true;
            }
            if ($args) {
                $this->properties[$name] = $args[0];
            }
            return $this->properties[$name];
        }

         /** @var ReflectionMethod $method */
        [$method, $callOnce] = $this->propertyMethod($name);

        if (!$assert) {
            return (bool)$method;
        }

        if (!$method) {
            fail\domain('missing magic-property %s in %s', $name, static::class);
        }

        ($args && !$method->getNumberOfParameters()) && fail\domain(
            'class %s has read-only access for the magic-property: %s',
            static::class,
            $name
        );

        $v = ($v = $this->{$method->name}(...$args)) instanceof Map\Value ? $v->value : $v;
        $callOnce && $this->properties[$name] = $v;

        return $v;
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
