<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

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
 * @param mixed $candidate
 * @param bool $cast
 * @param bool|callable $onError
 * @return array|iterable|\Traversable
 * @throws \InvalidArgumentException
 */
function toIterable($candidate, $cast = false, $onError = true)
{
    if (isIterable($candidate)) {
        return $candidate;
    }
    if ($cast) {
        return (array)$candidate;
    }
    $exception = new \InvalidArgumentException('Argument $candidate must be iterable');
    if ($onError === true) {
        throw $exception;
    }
    return is_callable($onError) ? $onError($candidate, $exception) : null;
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
 * @param mixed $value
 * @param iterable|mixed $in
 * @param bool $strict
 *
 * @return bool
 */
function hasValue($value, $in, $strict = true)
{
    return isIterable($in) && in_array($value, toMap($in), $strict);
}

/**
 * Convert the given candidate to an associative array and map/filter its values and keys if a callback is passed
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

 * - grouping with Value object @see mapGroup
 *
 * @see array_walk
 * @see array_filter
 * @see iterator_apply
 *
 * @param mixed $candidate
 * @param bool|callable $castOrCallable
 * @param bool $cast
 * @return array
 */
function traverse($candidate, $castOrCallable = null, $cast = null)
{
    if (!is_callable($castOrCallable)) {
        return toMap($candidate, $castOrCallable);
    }

    $null = mapNull();
    $break = mapBreak();
    $map = [];
    $iterable = toIterable($candidate, $cast);
    foreach ($iterable as $key => $sourceValue) {
        $value = $castOrCallable($sourceValue, $key);

        if (null === $value) {
            continue;
        }

        if (!$value instanceof Map\Value) {
            $map[$key] = $value;
            continue;
        }

        if ($null === $value) {
            $map[$key] = null;
            continue;
        }

        if ($break === $value) {
            break;
        }

        if (isset($value->key)) {
            $key = $value->key;
        }

        if (isset($value->value)) {
            $sourceValue = $value->value;
        }

        $groups = &$map;
        foreach(isset($value->group) ? toIterable($value->group, true) : [] as $group) {
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            $groups = &$groups[$group];
        }

        $groups[$key] = $sourceValue;
    }
    return $map;
}

/**
 * Create a Fn instance
 *
 * @param iterable ...$iterable If more than one iterable argument is passed, they will be merged (replace)
 * @param callable $mapper
 *
 * @return Map
 */
function map($iterable = null, $mapper = null)
{
    if (count($args = func_get_args()) > 1) {
        if (!isIterable($last = toValues(sub($args, -1))[0]) && is_callable($last)) {
            return (new Map)->replace(...sub($args, 0, -1))->map($last);
        }
        return (new Map)->replace(...$args);
    }
    return new Map($iterable, $mapper);
}

/**
 * @param mixed $value
 * @param mixed $key
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
 * @return Map\Value
 */
function mapNull()
{
    static $null;
    if (!$null) {
        $null = mapValue();
    }
    return $null;
}

/**
 * Returned object is used to stop the iteration in the @see \fn\traverse() function
 *
 * @return Map\Value
 */
function mapBreak()
{
    static $break;
    if (!$break) {
        $break = mapValue();
    }
    return $break;
}

