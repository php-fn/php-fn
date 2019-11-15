<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Closure;
use Traversable;

class Sort
{
    protected const FLAGS = 0b001111;
    public const REG = SORT_REGULAR;          // 0b000000
    public const NUM = SORT_NUMERIC;          // 0b000001
    public const STR = SORT_STRING;           // 0b000010
    public const LOCALE = SORT_LOCALE_STRING; // 0b000101
    public const NAT = SORT_NATURAL;          // 0b000110
    public const CASE = SORT_FLAG_CASE;       // 0b001000
    public const KEYS = 0b010000;
    public const DESC = 0b100000; // @see SORT_DESC 0b000011
    public const ASC = 0b000000; // @see SORT_ASC 0b000100

    protected static $cache = [];
    protected $call;
    protected $args;

    public function __construct(Closure $call, ...$args)
    {
        $this->call = $call;
        $this->args = $args;
    }

    public static function by($flags, ...$args): self
    {
        if ($flags instanceof static) {
            return $flags;
        }
        if (is_array($flags)) {
            return self::fromArray($flags);
        }
        if ($flags instanceof Closure) {
            return self::fromClosure($flags, ...$args);
        }
        return self::fromFlags($flags);
    }

    public static function sort(iterable $data, ...$sorts): array
    {
        $sorters = [];
        foreach ($sorts ?: [self::REG] as $flags) {
            $sorters[] = self::by($flags);
        }
        $data = $data instanceof Traversable ? iterator_to_array($data) : (array)$data;
        return self::level($data, ...$sorters);
    }

    protected static function level($data, self ...$sorters): array
    {
        if ($sorter = array_shift($sorters)) {
            $data = $data instanceof Traversable ? iterator_to_array($data) : (array)$data;
            $data = call_user_func($sorter->call, $data, ...$sorter->args);
            foreach ($sorters ? $data : [] as $key => $sub) {
                $data[$key] = static::level($sub, ...$sorters);
            }
        }
        return $data;
    }

    protected static function fromClosure(Closure $mapper, ...$args): self
    {
        return new static(static function (array $array, int $flags = self::REG) use ($mapper): array {
            if (!$array) {
                return $array;
            }
            $data = [];
            $sorted = self::sort(Gen::map($mapper, $array), $flags);
            foreach (array_keys($sorted) as $key) {
                $data[$key] = $array[$key];
            }
            return $data;
        }, ...$args);
    }

    protected static function fromFn($fn, int $flags): self
    {
        return new static(
            static function (array $array, int $flags = self::REG) use ($fn): array {
                $fn($array, static::FLAGS & $flags);
                return $array;
            },
            $flags
        );
    }

    protected static function fromFlags(int $flags): self
    {
        if (isset(static::$cache[$flags])) {
            return static::$cache[$flags];
        }
        $byKeys = static::KEYS & $flags;
        $desc = static::DESC & $flags;
        if ($desc) {
            if ($byKeys) {
                return static::$cache[$flags] = static::fromFn('krsort', $flags);
            }
            return static::$cache[$flags] = static::fromFn('arsort', $flags);
        }
        if ($byKeys) {
            return static::$cache[$flags] = static::fromFn('ksort', $flags);
        }
        return static::$cache[$flags] = static::fromFn('asort', $flags);
    }

    protected static function fromArray(array $config): self
    {
        $calls = [];
        $args = [];
        $i = 0;
        foreach ($config as $key => $value) {
            $sorter = static::multiSort($key, $value);
            $flags = ($sorter->args[0] ?? static::REG);
            $calls[$i] = $sorter->call;
            $args[$i++] = [];
            $args[$i++] = (static::DESC & $flags) ? SORT_DESC : SORT_ASC;
            $args[$i++] = static::FLAGS & $flags;
        }

        return new static(static function ($array) use ($calls, $args) {
            foreach ($array as $key => $value) {
                foreach ($calls as $i => $call) {
                    $args[$i][] = $call($value, $key);
                }
            }
            $args[] = &$array;
            array_multisort(...$args);
            return $array;
        });
    }

    protected static function multiSort($key, $value): self
    {
        if (is_int($key) && is_int($value)) {
            return (static::KEYS & $value) ? static::byKey($value) : static::byValue($value);
        }
        if ($value instanceof Closure) {
            return new static($value);
        }
        if ($value instanceof self) {
            return $value;
        }
        return is_string($value) ? static::byMember($value) : static::byMember($key, $value);
    }

    protected static function byKey(int $flags = self::REG): self
    {
        return static::$cache[__METHOD__][$flags] ?? static::$cache[__METHOD__][$flags] = new static(
                static function (...$args) {
                    return $args[1];
                },
                $flags
            );
    }

    protected static function byValue(int $flags = self::REG): self
    {
        return static::$cache[__METHOD__][$flags] ?? static::$cache[__METHOD__][$flags] = new static(
            static function ($value) {
                return $value;
            },
            $flags
        );
    }

    protected static function byMember($member, int $flags = self::REG): self
    {
        if ($member[0] === '$') {
            [, $member] = explode('$', $member);
            $fn = static function ($value) use ($member) {
                return $value->$member ?? null;
            };
        } else if (strpos($member, '(')) {
            [$member] = explode('(', $member);
            $fn = static function ($value) use ($member) {
                return $value->$member();
            };
        } else {
            $fn = static function ($value) use ($member) {
                return $value[$member] ?? null;
            };
        }
        return new static($fn, $flags);
    }
}
