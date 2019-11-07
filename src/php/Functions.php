<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ReflectionFunction;
use Traversable;

class Functions
{
    /**
     * @param array $args
     * @return callable[]
     */
    public static function lastCallable(array &$args): array
    {
        if (!$args) {
            return [];
        }

        $last = array_pop($args);
        if ($args && !is_iterable($last) && isCallable($last)) {
            return [$last];
        }

        $args[] = $last;
        return [];
    }

    /**
     * Call all functions one by one with softly converted iterables to arrays.
     *
     * @param callable[]|bool[] $functions
     * @param iterable[] ...$args
     * @return array|mixed
     */
    public static function chainIterables(array $functions, ...$args)
    {
        if (!$args) {
            return [];
        }
        $callable = self::lastCallable($args);
        $result = [];
        foreach ($args as $candidate) {
            $result[] = self::toArray($candidate);
        }
        foreach ($functions as $function => $variadic) {
            if (is_numeric($function)) {
                $function = $variadic;
                $variadic = false;
            }
            $result = $variadic ? $function(...$result) : $function($result);
        }
        return $callable ? traverse($result, ...$callable) : $result;
    }

    /**
     * Convert the given candidate to an iterable entity
     *
     * @param iterable|mixed $candidate
     * @param bool $cast
     * @return iterable
     */
    public static function toTraversable($candidate, $cast = false): iterable
    {
        if (is_iterable($candidate)) {
            return $candidate;
        }
        $cast ?: Php::fail('argument $candidate must be traversable');
        return (array)$candidate;
    }

    /**
     * Convert the given candidate to an associative array
     *
     * @param iterable|mixed $candidate
     * @param bool $cast
     * @return array
     */
    public static function toArray($candidate, $cast = false): array
    {
        if (is_array($candidate = self::toTraversable($candidate, $cast))) {
            return $candidate;
        }
        return iterator_to_array($candidate);
    }

    /**
     * Convert the given candidate to an array
     *
     * @param mixed $candidate
     * @param bool $cast
     * @return array
     */
    public static function toValues($candidate, $cast = false): array
    {
        if (is_array($candidate = self::toTraversable($candidate, $cast))) {
            return array_values($candidate);
        }
        return iterator_to_array($candidate, false);
    }

    /**
     * @param Traversable   $inner
     * @param bool          $leavesOnly
     * @param callable|null $mapper
     *
     * @return Traversable
     */
    public static function recursive(Traversable $inner, $leavesOnly, callable $mapper = null): Traversable
    {
        $mode = $leavesOnly ? Map\Path::LEAVES_ONLY : Map\Path::SELF_FIRST;
        $it = new Map\Path($inner, $mode);
        $class = get_class($inner);

        if (!$mapper) {
            return new $class($it);
        }

        foreach ((new ReflectionFunction($mapper))->getParameters() as $parameter) {
            if (($parClass = $parameter->getClass()) && $parClass->getName() === Map\Path::class) {
                $pos = $parameter->getPosition();
                return new $class($it, static function (...$args) use ($it, $mapper, $pos) {
                    return $mapper(...merge(array_slice($args, 0, $pos), [$it], array_slice($args, $pos)));
                });
            }
        }

        return new $class($it, static function (...$args) use ($mapper) {
            return $mapper(...$args);
        });
    }

}