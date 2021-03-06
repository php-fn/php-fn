<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayAccess;
use ArrayIterator;
use Closure;
use DI\CompiledContainer;
use DI\Definition\Source\DefinitionSource;
use EmptyIterator;
use Illuminate\Support\Str;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use Php\DI\Container;
use Php\DI\ContainerConfiguration;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use RuntimeException;
use stdClass;
use Traversable;

abstract class Php
{
    /**
     * Pop the element (of any specified type) off the end of array
     *
     * @param          $args
     * @param string   ...$types
     * @return mixed|null
     */
    public static function pop(&$args, string ...$types)
    {
        if (!$args || !is_array($args)) {
            return null;
        }
        $last = array_pop($args);
        if (!$types) {
            return $last;
        }
        foreach ($args ? $types : [] as $type) {
            if ($last instanceof $type) {
                return $last;
            }
        }
        $args[] = $last;
        return null;
    }

    public static function break(): stdClass
    {
        static $obj;
        return $obj ?? $obj = new stdClass();
    }

    /**
     * value mapping: function($value, $key) {yield $value;}
     * key mapping: function($value, $key) {yield [$key] => $value;}
     * grouping: function($value, $key) {yield [level1, level2, $key] => $value;}
     *
     * @param mixed ...$args
     * @return Gen
     */
    public static function gen(...$args): Gen
    {
        return new Gen(...$args);
    }

    public static function arr(...$args): array
    {
        return iterator_to_array(self::gen(...$args));
    }

    public static function ascii($value, $lang = 'en'): string
    {
        return Str::ascii($value, $lang);
    }

    public static function sort(iterable $data, ...$sorts): array
    {
        return Sort::sort(...func_get_args());
    }

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
            $type = $var instanceof Closure ? 'callable' : get_class($var);
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
                if (!is_scalar($replace) && !method_exists($replace, '__toString')) {
                    return null;
                }
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
     * @return Map\Value
     */
    public static function mapValue(...$args): Map\Value
    {
        return new Map\Value(...$args);
    }

    /**
     * @param mixed $key
     * @return Map\Value
     */
    public static function mapKey($key): Map\Value
    {
        return self::mapValue()->andKey($key);
    }

    /**
     * @param mixed $group
     * @return Map\Value
     */
    public static function mapGroup($group): Map\Value
    {
        return self::mapValue()->andGroup($group);
    }

    /**
     * @param iterable|callable $children
     * @return Map\Value
     */
    public static function mapChildren($children): Map\Value
    {
        return self::mapValue()->andChildren($children);
    }

    /**
     * @param string|iterable|\Closure $value
     * @param string $key column to
     * @return Map\RowMapper
     */
    public static function mapRow($value, $key = null, ...$group): Map\RowMapper
    {
        return new Map\RowMapper($key, $value, ...$group);
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
        return is_iterable($in) && array_key_exists($key, self::toArray($in));
    }

    /**
     * @param mixed $value
     * @param iterable|mixed $in
     * @param bool $strict
     * @return bool
     */
    public static function hasValue($value, $in, $strict = true): bool
    {
        return is_iterable($in) && in_array($value, self::toArray($in), $strict);
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
            return self::toArray($traversable);
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

            if (!$mapped instanceof Map\Value) {
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
            foreach (self::toTraversable($mapped->group, true) as $group) {
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
     * @return Map
     */
    public static function map(...$iterable): Map
    {
        $callable = self::lastCallable($iterable);
        if (count($iterable) === 1) {
            return new Map($iterable[0], ...$callable);
        }
        $merged = (new Map)->merge(...$iterable);
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
        return self::chainIterables(['array_merge' => true], ...$iterable);
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
        $callable = self::lastCallable($iterable);
        $functions = count($iterable) > 1 ? ['array_merge' => true, 'array_keys'] : ['array_keys' => true];
        self::chainIterables($functions, ...$iterable);
        return self::chainIterables($functions, ...$iterable, ...$callable);
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
        $callable = self::lastCallable($iterable);
        return self::merge(...self::traverse($iterable, function ($candidate) {
            return self::toValues($candidate);
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
        return self::chainIterables(['array_replace' => true], ...$iterable);
    }

    public static function every(iterable $iterable = [], Closure $closure = null): bool
    {
        foreach ($iterable as $key => $value) {
            if (!($closure ? $closure($value, $key) : $value)) {
                return false;
            }
        }
        return true;
    }

    public static function some(iterable $iterable = [], Closure $closure = null): bool
    {
        foreach ($iterable as $key => $value) {
            if ($closure ? $closure($value, $key) : $value) {
                return true;
            }
        }
        return false;
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
        $callable = self::lastCallable($iterable);
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
        $callable = self::lastCallable($iterable);
        return $callable ? self::map(...$iterable)->flatten(...$callable)->traverse : self::map(...$iterable)->flatten;
    }

    /**
     * @param array $args
     * @return callable[]
     */
    protected static function lastCallable(array &$args): array
    {
        if (!$args) {
            return [];
        }

        $last = array_pop($args);
        if ($args && !is_iterable($last) && self::isCallable($last)) {
            return [$last];
        }

        $args[] = $last;
        return [];
    }

    /**
     * Call all functions one by one with softly converted iterables to arrays.
     *
     * @param callable[]|bool[] $functions
     * @param iterable[] ...$args
     * @return array|mixed
     */
    protected static function chainIterables(array $functions, ...$args)
    {
        if (!$args) {
            return [];
        }
        $callable = self::lastCallable($args);
        $result = [];
        foreach ($args as $candidate) {
            $result[] = self::toArray($candidate);
        }
        foreach ($functions as $function => $variadic) {
            if (is_numeric($function)) {
                $function = $variadic;
                $variadic = false;
            }
            $result = $variadic ? $function(...$result) : $function($result);
        }
        return $callable ? self::traverse($result, ...$callable) : $result;
    }

    /**
     * @param Traversable   $inner
     * @param bool          $leavesOnly
     * @param callable|null $mapper
     *
     * @return Traversable
     */
    public static function recursive(Traversable $inner, $leavesOnly, callable $mapper = null): Traversable
    {
        $mode = $leavesOnly ? Map\Path::LEAVES_ONLY : Map\Path::SELF_FIRST;
        $it = new Map\Path($inner, $mode);
        $class = get_class($inner);

        if (!$mapper) {
            return new $class($it);
        }

        foreach ((new ReflectionFunction($mapper))->getParameters() as $parameter) {
            if (($parClass = $parameter->getClass()) && $parClass->getName() === Map\Path::class) {
                $pos = $parameter->getPosition();
                return new $class($it, static function (...$args) use ($it, $mapper, $pos) {
                    return $mapper(...self::merge(array_slice($args, 0, $pos), [$it], array_slice($args, $pos)));
                });
            }
        }

        return new $class($it, static function (...$args) use ($mapper) {
            return $mapper(...$args);
        });
    }

    /**
     * Convert the given candidate to an associative array
     *
     * @param iterable|mixed $candidate
     * @param bool $cast
     * @return array
     */
    public static function toArray($candidate, $cast = false): array
    {
        if (is_array($candidate = self::toTraversable($candidate, $cast))) {
            return $candidate;
        }
        return iterator_to_array($candidate);
    }

    /**
     * Convert the given candidate to an iterable entity
     *
     * @param iterable|mixed $candidate
     * @param bool $cast
     * @return iterable
     */
    protected static function toTraversable($candidate, $cast = false): iterable
    {
        if (is_iterable($candidate)) {
            return $candidate;
        }
        $cast ?: self::fail('argument $candidate must be traversable');
        return (array)$candidate;
    }

    /**
     * Convert the given candidate to an array
     *
     * @param mixed $candidate
     * @param bool $cast
     * @return array
     */
    public static function toValues($candidate, $cast = false): array
    {
        if (is_array($candidate = self::toTraversable($candidate, $cast))) {
            return array_values($candidate);
        }
        return iterator_to_array($candidate, false);
    }

    /**
     * Convert the given candidate to an iterator
     *
     * @param mixed $candidate
     * @return Iterator
     */
    public static function iter($candidate = null): Iterator
    {
        if (!func_num_args()) {
            return new EmptyIterator;
        }

        if ($candidate instanceof Closure) {
            $candidate = $candidate();
        }

        if (is_array($candidate)) {
            return new ArrayIterator($candidate);
        }

        $counter = 0;
        while ($candidate instanceof IteratorAggregate) {
            if ($counter++ > 10) {
                throw new RuntimeException('$candidate::getIterator is too deep');
            }

            /** @var mixed $temp */
            if (($temp = $candidate->getIterator()) === $candidate) {
                throw new RuntimeException('Implementation $candidate::getIterator returns the same instance');
            }
            $candidate = $temp;
        }

        if ($candidate instanceof Traversable && !$candidate instanceof Iterator) {
            return new IteratorIterator($candidate);
        }

        if (!$candidate instanceof Iterator) {
            throw new RuntimeException('Argument $candidate must be iterable');
        }

        return $candidate;
    }

    /**
     * Create a container from the given definitions.
     * If the last parameter is a callable it will be invoked to get the container configuration.
     * If the last parameter is TRUE the container will be auto(by reflections) wired.
     *
     * @param string|array|DefinitionSource|callable|true ...$args
     * @return Container|CompiledContainer
     */
    public static function di(...$args): ContainerInterface
    {
        return ContainerConfiguration::create(...$args)->container();
    }
}