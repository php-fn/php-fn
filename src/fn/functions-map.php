<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn {

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
     * @return array|iterable|\Traversable|null
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
     * Convert the given candidate to an associative array and map/filter its values and keys if a callback is passed
     *
     * supports:
     *
     * - value mapping
     *  - directly (by return)
     *  - with Value object @see map\value()
     *
     * - key mapping
     *  - directly (by reference)
     *  - with Value object @see map\key()
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

        $null = map\null();
        $stop = map\stop();
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

            if ($stop === $value) {
                break;
            }

            if (isset($value->key)) {
                $key = $value->key;
            }

            if (isset($value->value)) {
                $sourceValue = $value->value;
            }

            $map[$key] = $sourceValue;
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
}

namespace fn\map {

    use fn;

    /**
     * @param mixed $value
     * @param mixed $key
     * @param mixed $children
     * @return Value
     */
    function value(...$args)
    {
        return new fn\Map\Value(...$args);
    }

    /**
     * @param mixed $key
     * @return Value
     */
    function key($key)
    {
        return value()->andKey($key);
    }

    /**
     * Returned object is used to mark the value as NULL in the @see \fn\traverse() function,
     * since NULL itself is used to filter/skip values
     *
     * @return Value
     */
    function null()
    {
        static $null;
        if (!$null) {
            $null = value();
        }
        return $null;
    }

    /**
     * Returned object is used to stop the iteration in the @see \fn\traverse() function
     *
     * @return Value
     */
    function stop()
    {
        static $stop;
        if (!$stop) {
            $stop = value();
        }
        return $stop;
    }

    /**
     * @param iterable|callable $children
     * @return Value
     */
    function children($children)
    {
        return value()->andChildren($children);
    }
}
