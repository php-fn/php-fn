<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use Iterator;
use IteratorIterator;
use stdClass;
use Traversable;

/**
 * @param string|int $key
 * @param iterable|mixed $in
 *
 * @return bool
 */
function hasKey($key, $in): bool
{
    if ((is_array($in) || $in instanceof ArrayAccess || is_scalar($in)) && isset($in[$key])) {
        return true;
    }
    if ($in instanceof ArrayAccess) {
        return false;
    }
    return is_iterable($in) && array_key_exists($key, Functions::toArray($in));
}

/**
 * @param string|int $index
 * @param array|ArrayAccess|iterable|string $in
 * @param mixed $default
 * @return mixed
 */
function at($index, $in, $default = null)
{
    if ((is_array($in) || $in instanceof ArrayAccess || is_scalar($in)) && isset($in[$index])) {
        return $in[$index];
    }
    if (is_iterable($in) && array_key_exists($index, $map = Functions::toArray($in))) {
        return $map[$index];
    }
    func_num_args() > 2 ?: fail\range('undefined index: %s', $index);
    return $default;
}

/**
 * @param mixed $value
 * @param iterable|mixed $in
 * @param bool $strict
 * @return bool
 */
function hasValue($value, $in, $strict = true): bool
{
    return is_iterable($in) && in_array($value, Functions::toArray($in), $strict);
}

/**
 * Convert the given candidate to an associative array and map/filter/group its values and keys if a callback is passed
 *
 * supports:
 *
 * - value mapping
 *  - directly (by return)
 *  - with Value object @see mapValue
 *
 * - key mapping
 *  - directly (by reference)
 *  - with Value object @see mapKey
 *
 * - grouping with Value object @see mapGroup
 *
 * @see array_walk
 * @see array_filter
 * @see iterator_apply
 *
 * @param iterable|mixed $traversable
 * @param callable $callable
 * @param bool $reset Should the iterable be reset before traversing?
 * @return array
 */
function traverse($traversable, callable $callable = null, $reset = true): array
{
    if (!$callable) {
        return Functions::toArray($traversable);
    }
    if (!($isArray = is_array($traversable)) && !$traversable instanceof Iterator) {
        $traversable instanceof Traversable ?: fail\argument('argument $traversable must be traversable');
        $traversable = new IteratorIterator($traversable);
    }
    static $break, $null;
    if (!$break) {
        $null = mapNull();
        $break = mapBreak();
    }
    $map = [];
    if ($reset) {
        $isArray ? reset($traversable) : $traversable->rewind();
    }
    while ($isArray ? key($traversable) !== null : $traversable->valid()) {
        if ($isArray) {
            $current = current($traversable);
            $key     = key($traversable);
            $mapped  = $callable($current, $key);
            next($traversable);
        } else {
            $current = $traversable->current();
            $key     = $traversable->key();
            $mapped  = $callable($current, $key);
            $traversable->next();
        }

        if (null === $mapped) {
            continue;
        }

        if ($null === $mapped) {
            $map[$key] = null;
            continue;
        }

        if ($break === $mapped) {
            break;
        }

        if (!$mapped instanceof Map\Value) {
            $map[$key] = $mapped;
            continue;
        }

        if ($mapped->key !== null) {
            $key = $mapped->key;
        }

        if ($mapped->value !== null) {
            $current = $mapped->value === $null ? null : $mapped->value;
        }

        $groups = &$map;
        foreach (Functions::toTraversable($mapped->group, true) as $group) {
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            $groups = &$groups[$group];
        }
        $groups[$key] = $current;
    }
    return $map;
}

/**
 * @param iterable|callable ...$iterable If more than one iterable argument is passed, then they will be merged
 * @return Map
 */
function map(...$iterable): Map
{
    $callable = Functions::lastCallable($iterable);
    if (count($iterable) === 1) {
        return new Map($iterable[0], ...$callable);
    }
    $merged = (new Map)->merge(...$iterable);
    return $callable ? $merged->then(...$callable) : $merged;
}

/**
 * Merge (@see array_merge) all passed iterables.
 * The last argument can be a callable, in that case it will be applied to each element of the merged result.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function merge(...$iterable): array
{
    return Functions::chainIterables(['array_merge' => true], ...$iterable);
}

/**
 * Return keys (@see array_keys) from the iterable. If multiple iterables are passed they will be merged before.
 * The last argument can be a callable, in that case it will be applied to all merged keys.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function keys(...$iterable): array
{
    $callable  = Functions::lastCallable($iterable);
    $functions = count($iterable) > 1 ? ['array_merge' => true, 'array_keys'] : ['array_keys' => true];
    Functions::chainIterables($functions, ...$iterable);
    return Functions::chainIterables($functions, ...$iterable, ...$callable);
}

/**
 * Return values (@see array_values) from the iterable.
 * If multiple iterables are passed they will be merged after conversion.
 * The last argument can be a callable, in that case it will be applied to all merged values.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function values(...$iterable): array
{
    $callable = Functions::lastCallable($iterable);
    return merge(...traverse($iterable, function($candidate) {
        return Functions::toValues($candidate);
    }), ...$callable);
}

/**
 * Mixin (@see array_replace) all passed iterables.
 * The last argument can be a callable, in that case it will be applied to each element of the mixed in result.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function mixin(...$iterable): array
{
    return Functions::chainIterables(['array_replace' => true], ...$iterable);
}

/**
 * @param iterable|callable ...$iterable
 *
 * @return bool
 */
function every(...$iterable): bool
{
    return map(...$iterable)->every;
}

/**
 * @param iterable|callable ...$iterable
 *
 * @return bool
 */
function some(...$iterable): bool
{
    return map(...$iterable)->some;
}

/**
 * Flatten the given iterable recursively from root to leaves (@see RecursiveIteratorIterator::LEAVES_ONLY).
 * The last argument can be a callable, in that case it will be applied to each element of the merged result.
 *
 * @param iterable|callable ...$iterable If more than one iterable argument is passed they will be merged
 *
 * @return array
 */
function leaves(...$iterable): array
{
    $callable  = Functions::lastCallable($iterable);
    return $callable ? map(...$iterable)->leaves(...$callable)->values : map(...$iterable)->leaves;
}

/**
 * Traverse the given iterable recursively from root to leaves (@see RecursiveIteratorIterator::SELF_FIRST).
 * The last argument can be a callable, in that case it will be applied to each element of the merged result.
 *
 * @param iterable|callable ...$iterable If more than one iterable argument is passed they will be merged
 *
 * @return array
 */
function tree(...$iterable): array
{
    $callable  = Functions::lastCallable($iterable);
    return $callable ? map(...$iterable)->tree(...$callable)->values : map(...$iterable)->tree;
}

/**
 * Flatten the given iterables.
 * The last argument can be a callable, in that case it will be applied to each element of the merged result.
 *
 * @param iterable|callable ...$iterable If more than one iterable argument is passed they will be merged
 *
 * @return array
 */
function flatten(...$iterable): array
{
    $callable = Functions::lastCallable($iterable);
    return $callable ? map(...$iterable)->flatten(...$callable)->traverse : map(...$iterable)->flatten;
}

/**
 * @param string|iterable|\Closure $value
 * @param string $key column to
 * @return Map\RowMapper
 */
function mapRow($value, $key = null, ...$group): Map\RowMapper
{
    return new Map\RowMapper($key, $value, ...$group);
}

/**
 * @param mixed $value
 * @param mixed $key
 * @param mixed $group
 * @param mixed $children
 * @return Map\Value
 */
function mapValue(...$args): Map\Value
{
    return new Map\Value(...$args);
}

/**
 * @param mixed $key
 * @return Map\Value
 */
function mapKey($key): Map\Value
{
    return mapValue()->andKey($key);
}

/**
 * @param mixed $group
 * @return Map\Value
 */
function mapGroup($group): Map\Value
{
    return mapValue()->andGroup($group);
}

/**
 * @param iterable|callable $children
 * @return Map\Value
 */
function mapChildren($children): Map\Value
{
    return mapValue()->andChildren($children);
}

/**
 * Returned object is used to mark the value as NULL in the @see traverse function,
 * since NULL itself is used to filter/skip values
 *
 * @return stdClass
 */
function mapNull(): stdClass
{
    static $null;
    if (!$null) {
        $null = new stdClass;
    }
    return $null;
}

/**
 * Returned object is used to stop the iteration in the @see traverse function
 *
 * @return stdClass
 */
function mapBreak(): stdClass
{
    static $break;
    if (!$break) {
        $break = new stdClass;
    }
    return $break;
}
