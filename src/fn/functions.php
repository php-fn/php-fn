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
     * @return iterable|array|\Traversable
     * @throws \InvalidArgumentException
     */
    function iterable($candidate, $strict = true)
    {
        if (is_array($candidate) || $candidate instanceof \Traversable) {
            return $candidate;
        }
        if (!$strict) {
            return (array)$candidate;
        }
        throw new \InvalidArgumentException('Argument $candidate must be iterable');
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
}