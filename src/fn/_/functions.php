<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

namespace fn\_;
use fn;
use InvalidArgumentException;

/**
 * @param array $args
 * @return callable|null
 */
function lastCallable(array &$args)
{
    if (!$args) {
        return null;
    }
    if (!isTraversable($last = array_pop($args)) && fn\isCallable($last, true)) {
        if (!$args) {
            throw new InvalidArgumentException('single argument should not be a callable');
        }
        return $last;
    }
    $args[] = $last;
    return null;
}

/**
 * Check if the given candidate is iterable
 *
 * @param mixed $candidate
 * @return bool
 */
function isTraversable($candidate)
{
    return is_array($candidate) || $candidate instanceof \Traversable;
}

/**
 * Convert the given candidate to an iterable entity
 *
 * @param iterable|mixed $candidate
 * @param bool $cast
 * @return array|iterable|\Traversable
 * @throws InvalidArgumentException
 */
function toTraversable($candidate, $cast = false)
{
    if (isTraversable($candidate)) {
        return $candidate;
    }
    if ($cast) {
        return (array)$candidate;
    }
    throw new InvalidArgumentException('argument $candidate must be traversable');
}

/**
 * Convert the given candidate to an associative array
 *
 * @param iterable|mixed $candidate
 * @param bool $cast
 * @return array
 */
function toArray($candidate, $cast = false)
{
    if (is_array($candidate = toTraversable($candidate, $cast))) {
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
    if (is_array($candidate = toTraversable($candidate, $cast))) {
        return array_values($candidate);
    }
    return iterator_to_array($candidate, false);
}
