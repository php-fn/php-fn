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
     * @param $candidate
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
     * @param $candidate
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