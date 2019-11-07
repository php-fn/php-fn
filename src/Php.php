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
            return self::every($types, static function ($t) use ($type, $var) {
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

        return self::every($types, static function ($t) use ($type, $var) {
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
            $replacements = self::mixin(...$toMerge);
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

    /**
     * Returned object is used to mark the value as NULL in the @see traverse function,
     * since NULL itself is used to filter/skip values
     *
     * @return stdClass
     */
    public static function mapNull(): stdClass
    {
        static $null;
        if (!$null) {
            $null = new stdClass;
        }
        return $null;
    }

    /**
     * Returned object is used to stop the iteration in the @see traverse function
     *
     * @return stdClass
     */
    public static function mapBreak(): stdClass
    {
        static $break;
        if (!$break) {
            $break = new stdClass;
        }
        return $break;
    }

    /**
     * @param mixed $value
     * @param mixed $key
     * @param mixed $group
     * @param mixed $children
     * @return Php\Map\Value
     */
    public static function mapValue(...$args): Php\Map\Value
    {
        return new Php\Map\Value(...$args);
    }

    /**
     * @param mixed $key
     * @return Php\Map\Value
     */
    public static function mapKey($key): Php\Map\Value
    {
        return self::mapValue()->andKey($key);
    }

    /**
     * @param mixed $group
     * @return Php\Map\Value
     */
    public static function mapGroup($group): Php\Map\Value
    {
        return self::mapValue()->andGroup($group);
    }

    /**
     * @param iterable|callable $children
     * @return Php\Map\Value
     */
    public static function mapChildren($children): Php\Map\Value
    {
        return self::mapValue()->andChildren($children);
    }

    /**
     * @param string|iterable|\Closure $value
     * @param string $key column to
     * @return Php\Map\RowMapper
     */
    public static function mapRow($value, $key = null, ...$group): Php\Map\RowMapper
    {
        return new Php\Map\RowMapper($key, $value, ...$group);
    }

    /**
     * @param string|int $key
     * @param iterable|mixed $in
     *
     * @return bool
     */
    public static function hasKey($key, $in): bool
    {
        if ((is_array($in) || $in instanceof ArrayAccess || is_scalar($in)) && isset($in[$key])) {
            return true;
        }
        if ($in instanceof ArrayAccess) {
            return false;
        }
        return is_iterable($in) && array_key_exists($key, Php\Functions::toArray($in));
    }

    /**
     * @param string|int $index
     * @param array|ArrayAccess|iterable|string $in
     * @param mixed $default
     * @return mixed
     */
    public static function at($index, $in, $default = null)
    {
        if ((is_array($in) || $in instanceof ArrayAccess || is_scalar($in)) && isset($in[$index])) {
            return $in[$index];
        }
        if (is_iterable($in) && array_key_exists($index, $map = Php\Functions::toArray($in))) {
            return $map[$index];
        }
        func_num_args() > 2 ?: self::fail('undefined index: %s', $index);
        return $default;
    }

    /**
     * @param mixed $value
     * @param iterable|mixed $in
     * @param bool $strict
     * @return bool
     */
    public static function hasValue($value, $in, $strict = true): bool
    {
        return is_iterable($in) && in_array($value, Php\Functions::toArray($in), $strict);
    }

    /**
     * Convert the given candidate to an associative array and map/filter/group its values and keys if a callback is passed
     *
     * supports:
     *
     * - value mapping
     *  - directly (by return)
     *  - with Value object @see self::mapValue
     *
     * - key mapping
     *  - directly (by reference)
     *  - with Value object @see self::mapKey
     *
     * - grouping with Value object @see self::mapGroup
     *
     * @see array_walk
     * @see array_filter
     * @see iterator_apply
     *
     * @param iterable|mixed $traversable
     * @param callable $callable
     * @param bool $reset Should the iterable be reset before traversing?
     * @return array
     */
    public static function traverse($traversable, callable $callable = null, $reset = true): array
    {
        if (!$callable) {
            return Php\Functions::toArray($traversable);
        }
        if (!($isArray = is_array($traversable)) && !$traversable instanceof Iterator) {
            $traversable instanceof Traversable ?: self::fail('argument $traversable must be traversable');
            $traversable = new IteratorIterator($traversable);
        }
        static $break, $null;
        if (!$break) {
            $null = self::mapNull();
            $break = self::mapBreak();
        }
        $map = [];
        if ($reset) {
            $isArray ? reset($traversable) : $traversable->rewind();
        }
        while ($isArray ? key($traversable) !== null : $traversable->valid()) {
            if ($isArray) {
                $current = current($traversable);
                $key = key($traversable);
                $mapped = $callable($current, $key);
                next($traversable);
            } else {
                $current = $traversable->current();
                $key = $traversable->key();
                $mapped = $callable($current, $key);
                $traversable->next();
            }

            if (null === $mapped) {
                continue;
            }

            if ($null === $mapped) {
                $map[$key] = null;
                continue;
            }

            if ($break === $mapped) {
                break;
            }

            if (!$mapped instanceof Php\Map\Value) {
                $map[$key] = $mapped;
                continue;
            }

            if ($mapped->key !== null) {
                $key = $mapped->key;
            }

            if ($mapped->value !== null) {
                $current = $mapped->value === $null ? null : $mapped->value;
            }

            $groups = &$map;
            foreach (Php\Functions::toTraversable($mapped->group, true) as $group) {
                if (!isset($groups[$group])) {
                    $groups[$group] = [];
                }
                $groups = &$groups[$group];
            }
            $groups[$key] = $current;
        }
        return $map;
    }

    /**
     * @param iterable|callable ...$iterable If more than one iterable argument is passed, then they will be merged
     * @return Php\Map
     */
    public static function map(...$iterable): Php\Map
    {
        $callable = Php\Functions::lastCallable($iterable);
        if (count($iterable) === 1) {
            return new Php\Map($iterable[0], ...$callable);
        }
        $merged = (new Php\Map)->merge(...$iterable);
        return $callable ? $merged->then(...$callable) : $merged;
    }

    /**
     * Merge (@see array_merge) all passed iterables.
     * The last argument can be a callable, in that case it will be applied to each element of the merged result.
     *
     * @param iterable|callable ...$iterable
     *
     * @return array
     */
    public static function merge(...$iterable): array
    {
        return Php\Functions::chainIterables(['array_merge' => true], ...$iterable);
    }

    /**
     * Return keys (@see array_keys) from the iterable. If multiple iterables are passed they will be merged before.
     * The last argument can be a callable, in that case it will be applied to all merged keys.
     *
     * @param iterable|callable ...$iterable
     *
     * @return array
     */
    public static function keys(...$iterable): array
    {
        $callable = Php\Functions::lastCallable($iterable);
        $functions = count($iterable) > 1 ? ['array_merge' => true, 'array_keys'] : ['array_keys' => true];
        Php\Functions::chainIterables($functions, ...$iterable);
        return Php\Functions::chainIterables($functions, ...$iterable, ...$callable);
    }

    /**
     * Return values (@see array_values) from the iterable.
     * If multiple iterables are passed they will be merged after conversion.
     * The last argument can be a callable, in that case it will be applied to all merged values.
     *
     * @param iterable|callable ...$iterable
     *
     * @return array
     */
    public static function values(...$iterable): array
    {
        $callable = Php\Functions::lastCallable($iterable);
        return self::merge(...self::traverse($iterable, function ($candidate) {
            return Php\Functions::toValues($candidate);
        }), ...$callable);
    }

    /**
     * Mixin (@see array_replace) all passed iterables.
     * The last argument can be a callable, in that case it will be applied to each element of the mixed in result.
     *
     * @param iterable|callable ...$iterable
     *
     * @return array
     */
    public static function mixin(...$iterable): array
    {
        return Php\Functions::chainIterables(['array_replace' => true], ...$iterable);
    }

    /**
     * @param iterable|callable ...$iterable
     *
     * @return bool
     */
    public static function every(...$iterable): bool
    {
        return self::map(...$iterable)->every;
    }

    /**
     * @param iterable|callable ...$iterable
     *
     * @return bool
     */
    public static function some(...$iterable): bool
    {
        return self::map(...$iterable)->some;
    }

    /**
     * Flatten the given iterable recursively from root to leaves (@see RecursiveIteratorIterator::LEAVES_ONLY).
     * The last argument can be a callable, in that case it will be applied to each element of the merged result.
     *
     * @param iterable|callable ...$iterable If more than one iterable argument is passed they will be merged
     *
     * @return array
     */
    public static function leaves(...$iterable): array
    {
        $callable = Php\Functions::lastCallable($iterable);
        return $callable ? self::map(...$iterable)->leaves(...$callable)->values : self::map(...$iterable)->leaves;
    }

    /**
     * Traverse the given iterable recursively from root to leaves (@see RecursiveIteratorIterator::SELF_FIRST).
     * The last argument can be a callable, in that case it will be applied to each element of the merged result.
     *
     * @param iterable|callable ...$iterable If more than one iterable argument is passed they will be merged
     *
     * @return array
     */
    public static function tree(...$iterable): array
    {
        $callable = Php\Functions::lastCallable($iterable);
        return $callable ? self::map(...$iterable)->tree(...$callable)->values : self::map(...$iterable)->tree;
    }

    /**
     * Flatten the given iterables.
     * The last argument can be a callable, in that case it will be applied to each element of the merged result.
     *
     * @param iterable|callable ...$iterable If more than one iterable argument is passed they will be merged
     *
     * @return array
     */
    public static function flatten(...$iterable): array
    {
        $callable = Php\Functions::lastCallable($iterable);
        return $callable ? self::map(...$iterable)->flatten(...$callable)->traverse : self::map(...$iterable)->flatten;
    }
}
