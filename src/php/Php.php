<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php;

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
            return every($types, static function ($t) use ($type, $var) {
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

        return every($types, static function ($t) use ($type, $var) {
            $t = (string)$t;
            $t === 'iterable' && $t = 'array';
            $t === 'callable' && is_callable($var) && $t = $type;
            return $t === $type;
        }) ? $types[0] ?? $type : '';
    }
}
