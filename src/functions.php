<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
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

    if (isCallable($lengthOrCallable)) {
        $callable = $lengthOrCallable;
    } else {
        $length = $lengthOrCallable;
        if (isCallable($encodingOrCallable)) {
            $callable = $encodingOrCallable;
        } else {
            $encoding = $encodingOrCallable;
            $callable = $callableOrNull;
        }
    }

    if (is_iterable($candidate)) {
        return traverse(array_slice(traverse($candidate), $start, $length, true), $callable);
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
function isCallable($candidate, $strict = true): bool
{
    if (!is_callable($candidate, !$strict) || ($strict && is_string($candidate) && !strpos($candidate, '::'))) {
        return false;
    }
    return true;
}

/**
 * Convert the given subject to a string.
 * If replacements are specified, the placeholders in the string will be substituted with them.
 * Placeholders can be specified within braces: {0}, {array_key}
 * or in the common @see sprintf format: %s, %d ...
 *
 * @param string $subject
 * @param string|array|mixed ...$replacements
 *
 * @return string
 */
function str($subject, ...$replacements): string
{
    $subject = (string)$subject;
    if (!$replacements) {
        return $subject;
    }
    if (strpos($subject, '{') !== false && strpos($subject, '}') !== false) {
        $toMerge = [0 => []];
        foreach ($replacements as $key => $replacement) {
            if (is_iterable($replacement)) {
                $toMerge[] = $replacement;
            } else {
                $toMerge[0][$key] = $replacement;
            }
        }
        $toMerge[] = function($replace, &$search) {
            $search = '{' . $search . '}';
            return (string)$replace;
        };
        $replacements = mixin(...$toMerge);
        return str_replace(array_keys($replacements), $replacements, $subject);
    }
    return vsprintf($subject, $replacements);
}

