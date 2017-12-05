<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

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
