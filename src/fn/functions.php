<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

namespace fn {

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
    function map($candidate, $castOrCallable = null, $cast = null)
    {
        if (!is_callable($castOrCallable)) {
            return to\map($candidate, $castOrCallable);
        }

        $null = map\null();
        $stop = map\stop();
        $map = [];
        $iterable = to\iterable($candidate, $cast);
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
     * for iterable:
     *  sub($iterable, $start)
     *  sub($iterable, $start, $callable = null)
     *  sub($iterable, $start, $length, $callable = null)
     *
     * for string:
     *  sub($string, $start)
     *  sub($string, $start, $callable = null)
     *  sub($string, $start, $length, $callable = null)
     *  sub($string, $start, $length, $encoding, $callable = null)
     *
     * @param iterable|string $candidate
     * @param int $start
     * @param int|callable $lengthOrCallable
     * @param string|callable $encodingOrCallable
     * @param callable $callableOrNull
     *
     * @return array|string
     */
    function sub($candidate, $start, $lengthOrCallable = null, $encodingOrCallable = null, $callableOrNull = null)
    {
        $length = null;
        $callable = null;
        $encoding = null;

        if (is_callable($lengthOrCallable)) {
            $callable = $lengthOrCallable;
        } else {
            $length = $lengthOrCallable;
            if (is_callable($encodingOrCallable)) {
                $callable = $encodingOrCallable;
            } else {
                $encoding = $encodingOrCallable;
                $callable = $callableOrNull;
            }
        }

        if ($iterable = to\iterable($candidate, false, false)) {
            return map(array_slice(map($iterable), $start, $length, true), $callable);
        }

        if ($encoding) {
            $subStr = mb_substr((string)$candidate, $start, $length, $encoding);
        } else {
            $subStr = mb_substr((string)$candidate, $start, $length);
        }

        return $callable ? $callable($subStr) : $subStr;
    }
}

namespace fn\map {

    use fn;

    /**
     * @param mixed [$value]
     * @param mixed [$key]
     * @param mixed [$children]
     * @return fn\Map\Value
     */
    function value()
    {
        return new fn\Map\Value(...func_get_args());
    }

    /**
     * @param mixed $key
     * @return fn\Map\Value
     */
    function key($key)
    {
        return value()->andKey($key);
    }

    /**
     * Returned object is used to mark the value as NULL in the @see \fn\map() function,
     * since NULL itself is used to filter/skip values
     *
     * @return fn\Map\Value
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
     * Returned object is used to stop the iteration in the @see \fn\map() function
     *
     * @return fn\Map\Value
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
     * @return fn\Map\Value
     */
    function children($children)
    {
        return value()->andChildren($children);
    }
}

namespace fn\to {

    /**
     * Convert the given candidate to an iterable entity
     *
     * @param mixed $candidate
     * @param bool $cast
     * @param bool|callable $onError
     * @return array|iterable|\Traversable|null
     * @throws \InvalidArgumentException
     */
    function iterable($candidate, $cast = false, $onError = true)
    {
        if (is_array($candidate) || $candidate instanceof \Traversable) {
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
     * @param mixed $candidate
     * @param bool $cast
     * @return array
     */
    function map($candidate, $cast = false)
    {
        if (is_array($candidate = iterable($candidate, $cast))) {
            return $candidate;
        }
        return iterator_to_array($candidate, true);
    }

    /**
     * Convert the given candidate to an array
     *
     * @param mixed $candidate
     * @param bool $cast
     * @return array
     */
    function values($candidate, $cast = false)
    {
        if (is_array($candidate = iterable($candidate, $cast))) {
            return array_values($candidate);
        }
        return iterator_to_array($candidate, false);
    }
}