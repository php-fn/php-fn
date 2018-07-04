<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

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
function hasKey($key, $in)
{
    if ((is_array($in) || $in instanceof ArrayAccess || is_scalar($in)) && isset($in[$key])) {
        return true;
    }
    if ($in instanceof ArrayAccess) {
        return false;
    }
    return _\isTraversable($in) && array_key_exists($key, _\toArray($in));
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
    if (_\isTraversable($in) && array_key_exists($index, $map = _\toArray($in))) {
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
function hasValue($value, $in, $strict = true)
{
    return _\isTraversable($in) && in_array($value, _\toArray($in), $strict);
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
function traverse($traversable, callable $callable = null, $reset = true)
{
    if (!$callable) {
        return _\toArray($traversable);
    }
    if (!($isArray = is_array($traversable)) && !$traversable instanceof Iterator) {
        $traversable instanceof Traversable ?: fail\argument('argument $traversable must be traversable');
        $traversable = new IteratorIterator($traversable);
    }
    $null = mapNull();
    $break = mapBreak();
    $map = [];
    if ($reset) {
        $isArray ? reset($traversable) : $traversable->rewind();
    }
    while ($isArray ? key($traversable) !== null : $traversable->valid()) {
        if ($isArray) {
            $current = current($traversable);
            $key = key($traversable);
            next($traversable);
        } else {
            $current = $traversable->current();
            $key = $traversable->key();
            $traversable->next();
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
        foreach (_\toTraversable($mapped->group, true) as $group) {
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
 * @param iterable|callable ...$iterable If more than one iterable argument is passed, they will be merged
 * @return Map
 */
function map(...$iterable)
{
    $callable = _\lastCallable($iterable);
    if (count($iterable) === 1) {
        return new Map($iterable[0], $callable);
    }
    $merged = (new Map)->merge(...$iterable);
    return $callable ? $merged->map($callable) : $merged;
}

/**
 * Merge (@see array_merge) all passed iterables.
 * The last argument can be a callable, in that case it will be applied to each element of the merged result.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function merge(...$iterable)
{
    return _\chainIterables(['array_merge' => true], ...$iterable);
}

/**
 * Return keys (@see array_keys) from the iterable. If multiple iterables are passed they will be merged before.
 * The last argument can be a callable, in that case it will be applied to all merged keys.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function keys(...$iterable)
{
    $callable  = _\lastCallable($iterable);
    $functions = count($iterable) > 1 ? ['array_merge' => true, 'array_keys'] : ['array_keys' => true];
    _\chainIterables($functions, ...$iterable, ...$iterable);
    return _\chainIterables($functions, ...$iterable, ...(array)$callable);
}

/**
 * Mixin (@see array_replace) all passed iterables.
 * The last argument can be a callable, in that case it will be applied to each element of the mixed in result.
 *
 * @param iterable|callable ...$iterable
 *
 * @return array
 */
function mixin(...$iterable)
{
    return _\chainIterables(['array_replace' => true], ...$iterable);
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
 * Returned object is used to mark the value as NULL in the @see traverse function,
 * since NULL itself is used to filter/skip values
 *
 * @return stdClass
 */
function mapNull()
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
function mapBreak()
{
    static $break;
    if (!$break) {
        $break = new stdClass;
    }
    return $break;
}
