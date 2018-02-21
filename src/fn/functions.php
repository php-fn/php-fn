<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

namespace fn;

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

    if (isCallable($lengthOrCallable, true)) {
        $callable = $lengthOrCallable;
    } else {
        $length = $lengthOrCallable;
        if (isCallable($encodingOrCallable, true)) {
            $callable = $encodingOrCallable;
        } else {
            $encoding = $encodingOrCallable;
            $callable = $callableOrNull;
        }
    }

    if (($iterable = toIterable($candidate, false, false)) || is_array($iterable)) {
        return traverse(array_slice(traverse($iterable), $start, $length, true), $callable);
    }

    if ($encoding) {
        $subStr = mb_substr((string)$candidate, $start, $length, $encoding);
    } else {
        $subStr = mb_substr((string)$candidate, $start, $length);
    }

    return $callable ? $callable($subStr) : $subStr;
}

/**
 * @param callable|mixed $candidate
 * @param bool $strict
 * @return bool
 */
function isCallable($candidate, $strict = false)
{
    if (!is_callable($candidate, !$strict) || ($strict && is_string($candidate) && !strpos($candidate, '::'))) {
        return false;
    }
    return true;
}
