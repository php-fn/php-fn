<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\_;

use fn;
use RecursiveIteratorIterator;
use ReflectionFunction;
use Traversable;

/**
 * @param array $args
 * @return callable[]
 */
function lastCallable(array &$args)
{
    if (!$args) {
        return [];
    }
    if (!is_iterable($last = array_pop($args)) && fn\isCallable($last, true)) {
        $args ?: fn\fail\argument('single argument should not be a callable');
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
function chainIterables(array $functions, ...$args)
{
    if (!$args) {
        return [];
    }
    $callable = lastCallable($args);
    $result = [];
    foreach ($args as $candidate) {
        $result[] = toArray($candidate);
    }
    foreach ($functions as $function => $variadic) {
        if (is_numeric($function)) {
            $function = $variadic;
            $variadic = false;
        }
        $result = $variadic ? $function(...$result) : $function($result);
    }
    return $callable ? fn\traverse($result, ...$callable) : $result;
}

/**
 * Convert the given candidate to an iterable entity
 *
 * @param iterable|mixed $candidate
 * @param bool $cast
 * @return iterable
 */
function toTraversable($candidate, $cast = false): iterable
{
    if (is_iterable($candidate)) {
        return $candidate;
    }
    $cast ?: fn\fail\argument('argument $candidate must be traversable');
    return (array)$candidate;
}

/**
 * Convert the given candidate to an associative array
 *
 * @param iterable|mixed $candidate
 * @param bool $cast
 * @return array
 */
function toArray($candidate, $cast = false): array
{
    if (is_array($candidate = toTraversable($candidate, $cast))) {
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
function toValues($candidate, $cast = false): array
{
    if (is_array($candidate = toTraversable($candidate, $cast))) {
        return array_values($candidate);
    }
    return iterator_to_array($candidate, false);
}

/**
 * Convert the given subject to a string.
 * If replacements are specified, the placeholders in the string will be substituted with them.
 * Placeholders can be specified within braces: {0}, {array_key}
 * or in the common @see sprintf format: %s, %d ...
 *
 * @param string $subject
 * @param string|array|mixed ...$replacements
 *
 * @return string
 */
function toString($subject, ...$replacements): string
{
    $subject = (string)$subject;
    if (!$replacements) {
        return $subject;
    }
    if (strpos($subject, '{') !== false && strpos($subject, '}') !== false) {
        $toMerge = [0 => []];
        foreach ($replacements as $key => $replacement) {
            if (is_iterable($replacement)) {
                $toMerge[] = $replacement;
            } else {
                $toMerge[0][$key] = $replacement;
            }
        }
        $toMerge[] = function($replace, &$search) {
            $search = '{' . $search . '}';
            return (string)$replace;
        };
        $replacements = fn\mixin(...$toMerge);
        return str_replace(array_keys($replacements), $replacements, $subject);
    }
    return vsprintf($subject, $replacements);
}

/** @noinspection PhpDocMissingThrowsInspection */
/**
 * @param Traversable   $inner
 * @param bool          $leavesOnly
 * @param callable|null $mapper
 *
 * @return Traversable
 */
function recursive(Traversable $inner, $leavesOnly, callable $mapper = null): Traversable
{
    $mode  = $leavesOnly ? RecursiveIteratorIterator::LEAVES_ONLY : RecursiveIteratorIterator::SELF_FIRST;
    $it    = new RecursiveIteratorIterator($inner, $mode);
    $class = get_class($inner);

    if (!$mapper) {
        return new $class($it);
    }

    foreach ((new ReflectionFunction($mapper))->getParameters() as $parameter) {
        if (($parClass = $parameter->getClass()) && $parClass->getName() === RecursiveIteratorIterator::class) {
            $pos = $parameter->getPosition();
            return new $class($it, function(...$args) use($it, $mapper, $pos) {
                return $mapper(...fn\merge(fn\sub($args, 0, $pos) , [$it], fn\sub($args, $pos)));
            });
        }
    }

    return new $class($it, function(...$args) use($mapper) {
        return $mapper(...$args);
    });
}

/**
 * @param string $class
 * @param string $message
 * @param mixed ...$replacements
 */
function fail($class, $message, ...$replacements): void
{
    throw new $class(toString($message, ...$replacements));
}
