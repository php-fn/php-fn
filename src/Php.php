<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

abstract class Php
{
    /**
     * Get a type of variable (int|bool|string|array|callable|iterable|::class)
     *
     * @param mixed $var
     * @param string[] ...$types validates if the variable is a type of every given entry
     *
     * @return string
     */
    public static function type($var, ...$types): string
    {
        if (is_object($var)) {
            $type = $var instanceof \Closure ? 'callable' : get_class($var);
            return Php\every($types, static function ($t) use ($type, $var) {
                $t = (string)$t;
                if ($t === $type || ($t === 'callable' && is_callable($var)) || ($t === 'iterable' && is_iterable($var))) {
                    return true;
                }
                return is_a($type, $t, true);
            }) ? $types[0] ?? $type : '';
        }

        if (is_bool($var)) {
            $type = 'bool';
        } else if (is_int($var)) {
            $type = 'int';
        } else if (is_float($var)) {
            $type = 'float';
        } else if (is_array($var)) {
            $type = 'array';
        } else if (is_string($var)) {
            $type = 'string';
        } else {
            $type = '';
        }

        return Php\every($types, static function ($t) use ($type, $var) {
            $t = (string)$t;
            $t === 'iterable' && $t = 'array';
            $t === 'callable' && is_callable($var) && $t = $type;
            return $t === $type;
        }) ? $types[0] ?? $type : '';
    }

    /**
     * @param string $message
     * @param string ...$replacements
     */
    public static function fail($message, ...$replacements): void
    {
        throw new RuntimeException(self::str($message, ...$replacements));
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
    public static function str($subject, ...$replacements): string
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
            $toMerge[] = static function ($replace, &$search) {
                $search = '{' . $search . '}';
                return (string)$replace;
            };
            $replacements = Php\mixin(...$toMerge);
            return str_replace(array_keys($replacements), $replacements, $subject);
        }
        return vsprintf($subject, $replacements);
    }

    /**
     * @param callable|mixed $candidate
     * @param bool $strict
     * @return bool
     */
    public static function isCallable($candidate, $strict = true): bool
    {
        return !(
            !is_callable($candidate, !$strict) ||
            ($strict && is_string($candidate) && !strpos($candidate, '::'))
        );
    }
}
