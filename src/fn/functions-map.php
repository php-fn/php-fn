<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use InvalidArgumentException;
use IteratorIterator;

/**
 * Check if the given candidate is iterable
 *
 * @param mixed $candidate
 * @return bool
 */
function isIterable($candidate)
{
    return is_array($candidate) || $candidate instanceof \Traversable;
}

/**
 * Convert the given candidate to an iterable entity
 *
 * @param iterable $iterable
 * @param bool $cast
 * @return array|iterable|\Traversable
 * @throws InvalidArgumentException
 */
function toIterable($iterable, $cast = false)
{
    if (isIterable($iterable)) {
        return $iterable;
    }
    if ($cast) {
        return (array)$iterable;
    }
    throw new InvalidArgumentException('argument $iterable must be iterable');
}

/**
 * Convert the given candidate to an associative array
 *
 * @param iterable|mixed $candidate
 * @param bool $cast
 * @return array
 */
function toMap($candidate, $cast = false)
{
    if (is_array($candidate = toIterable($candidate, $cast))) {
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
function toValues($candidate, $cast = false)
{
    if (is_array($candidate = toIterable($candidate, $cast))) {
        return array_values($candidate);
    }
    return iterator_to_array($candidate, false);
}

/**
 * @param string|int $key
 * @param iterable|mixed $in
 *
 * @return bool
 */
function hasKey($key, $in)
{
    if ((is_array($in) || $in instanceof \ArrayAccess || is_scalar($in)) && isset($in[$key])) {
        return true;
    }
    if ($in instanceof \ArrayAccess) {
        return false;
    }
    return isIterable($in) && array_key_exists($key, toMap($in));
}

/**
 * @param string|int $index
 * @param array|\ArrayAccess|iterable|string $in
 * @param mixed $default
 * @return mixed
 */
function at($index, $in, $default = null)
{
    if ((is_array($in) || $in instanceof \ArrayAccess || is_scalar($in)) && isset($in[$index])) {
        return $in[$index];
    }
    if (isIterable($in) && array_key_exists($index, $map = toMap($in))) {
        return $map[$index];
    }
    if (func_num_args() > 2) {
        return $default;
    }
    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
    throw new \OutOfRangeException(sprintf('undefined index: %s',
        $index
    ));
}

/**
 * @param mixed $value
 * @param iterable|mixed $in
 * @param bool $strict
 * @return bool
 */
function hasValue($value, $in, $strict = true)
{
    return isIterable($in) && in_array($value, toMap($in), $strict);
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
 * @param iterable|mixed $iterable
 * @param callable $callable
 * @param bool $reset Should the iterable be reset before traversing?
 * @return array
 */
function traverse($iterable, callable $callable = null, $reset = true)
{
    if (!$callable) {
        return toMap($iterable);
    }
    if (!($isArray = is_array($iterable)) && !$iterable instanceof \Iterator) {
        if (!$iterable instanceof \Traversable) {
            throw new InvalidArgumentException('argument $iterable must be iterable');
        }
        $iterable = new IteratorIterator($iterable);
    }
    $null = mapNull();
    $break = mapBreak();
    $map = [];
    if ($reset) {
        $isArray ? reset($iterable) : $iterable->rewind();
    }
    while ($isArray ? key($iterable) !== null : $iterable->valid()) {
        if ($isArray) {
            $current = current($iterable);
            $key = key($iterable);
            next($iterable);
        } else {
            $current = $iterable->current();
            $key = $iterable->key();
            $iterable->next();
        }

        if (null === $mapped = $callable($current, $key)) {
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
            $current = $mapped->value;
        }

        $groups = &$map;
        foreach (toIterable($mapped->group, true) as $group) {
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
 * @param iterable ...$iterable If more than one iterable argument is passed, they will be merged
 * @param callable $mapper
 * @return Map
 */
function map($iterable = null, $mapper = null)
{
    if (count($args = func_get_args()) > 1) {
        if (!isIterable($last = toValues(sub($args, -1))[0]) && isCallable($last, true)) {
            return (new Map)->merge(...sub($args, 0, -1))->map($last);
        }
        return (new Map)->merge(...$args);
    }
    return new Map($iterable, $mapper);
}

/**
 * @param string|iterable|\Closure $value
 * @param string $key column to
 * @return Map\RowMapper
 */
function mapRow($value, $key = null, ...$group)
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
function mapValue(...$args)
{
    return new Map\Value(...$args);
}

/**
 * @param mixed $key
 * @return Map\Value
 */
function mapKey($key)
{
    return mapValue()->andKey($key);
}

/**
 * @param mixed $group
 * @return Map\Value
 */
function mapGroup($group)
{
    return mapValue()->andGroup($group);
}

/**
 * @param iterable|callable $children
 * @return Map\Value
 */
function mapChildren($children)
{
    return mapValue()->andChildren($children);
}

/**
 * Returned object is used to mark the value as NULL in the @see \fn\traverse() function,
 * since NULL itself is used to filter/skip values
 *
 * @return \stdClass
 */
function mapNull()
{
    static $null;
    if (!$null) {
        $null = new \stdClass;
    }
    return $null;
}

/**
 * Returned object is used to stop the iteration in the @see \fn\traverse() function
 *
 * @return \stdClass
 */
function mapBreak()
{
    static $break;
    if (!$break) {
        $break = new \stdClass;
    }
    return $break;
}

