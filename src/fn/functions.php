<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

namespace fn\to {

    /**
     * Convert the given candidate to an iterable entity
     *
     * @param mixed $candidate
     * @param bool $strict
     * @param bool|callable $onError
     * @return array|iterable|\Traversable|false
     */
    function iterable($candidate, $strict = true, $onError = true)
    {
        if (is_array($candidate) || $candidate instanceof \Traversable) {
            return $candidate;
        }
        if (!$strict) {
            return (array)$candidate;
        }
        if ($onError) {
            $exception = new \InvalidArgumentException('Argument $candidate must be iterable');
            if (is_callable($onError)) {
                return call_user_func($onError, $candidate, $exception);
            }
            throw $exception;
        }
        return false;
    }

    /**
     * Convert the given candidate to an associative array
     *
     * @param mixed $candidate
     * @param bool $strict
     * @return array
     */
    function map($candidate, $strict = true)
    {
        if (is_array($candidate = iterable($candidate, $strict))) {
            return $candidate;
        }
        return iterator_to_array($candidate, true);
    }

    /**
     * Convert the given candidate to an array
     *
     * @param mixed $candidate
     * @param bool $strict
     * @return array
     */
    function values($candidate, $strict = true)
    {
        if (is_array($candidate = iterable($candidate, $strict))) {
            return array_values($candidate);
        }
        return iterator_to_array($candidate, false);
    }
}

namespace fn {

    /**
     * Convert the given candidate to an associative array and map/filter it if a callback is passed
     *
     * @see array_map
     * @see array_walk
     * @see array_filter
     * @see iterator_apply
     *
     * @param mixed $candidate
     * @param bool|callable $strictOrCallable
     * @param bool $strict
     * @return array
     */
    function map($candidate, $strictOrCallable = null, $strict = null)
    {
        if (!is_callable($strictOrCallable)) {
            return to\map($candidate, $strictOrCallable === null || $strictOrCallable);
        }

        $skip = skip();
        $stop = skip(true);
        $map = [];
        foreach (to\map($candidate, $strict === null || $strict) as $key => $value) {
            $value = call_user_func_array($strictOrCallable, [$value, &$key]);
            if ($skip === $value) {
                continue;
            }
            if ($stop === $value) {
                break;
            }
            $map[$key] = $value;
        }
        return $map;
    }

    /**
     * Return an object to control the iteration process
     *
     * @param bool $all FALSE => continue loop, TRUE => break loop
     *
     * @return \stdClass
     */
    function skip($all = false)
    {
        static $skipSingleton, $stopSingleton;

        if ($all) {
            if (!$stopSingleton) {
                $stopSingleton = new \stdClass();
            }
            return $stopSingleton;
        }

        if (!$skipSingleton) {
            $skipSingleton = new \stdClass();
        }
        return $skipSingleton;
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

        if ($iterable = to\iterable($candidate, true, false)) {
            return map(array_slice(map($iterable), $start, $length, true), $callable);
        }

        if ($encoding) {
            $subStr = mb_substr((string) $candidate, $start, $length, $encoding);
        } else {
            $subStr = mb_substr((string) $candidate, $start, $length);
        }

        return $callable ? $callable($subStr) : $subStr;
    }
}