<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Closure;
use Generator;
use IteratorAggregate;

class Gen implements IteratorAggregate
{
    private $closure;

    private $iterables = [];

    public function __construct(...$args)
    {
        $this->closure = Php::pop($args, Closure::class);
        foreach ($args as $i => $arg) {
            $arg = $arg instanceof Closure ? $arg() : $arg;
            is_iterable($arg) || Php::fail("argument #$i is not iterable");
            $this->iterables[] = $arg;
        }
    }

    public static function map(Closure $map = null, iterable... $iterables): Generator
    {
        $break = Php::break();
        $data = null;
        $pos = 0;
        $numKey = 0;
        foreach ($iterables as $iterable) {
            foreach ($iterable as $key => $value) {
                if ($map) {
                    $values = $map($value, $key, $pos);
                    $values instanceof Generator || $values = [$key => $values];
                } else if (is_array($key)) {
                    self::group($key, $value, $data);
                    continue;
                } else {
                    $values = [$key => $value];
                }
                foreach ($values as $k => $v) {
                    if ($v === $break) {
                        break 3;
                    }
                    if (is_array($k)) {
                        self::group($k, $v, $data);
                    } else if ($k === null) {
                        yield $numKey++ => $v;
                    } else {
                        yield is_int($key) ? $numKey++ : $key => $v;
                    }
                    $pos++;
                }
            }
        }
        if (is_array($data)) {
            yield from $data;
        }
    }

    private static function group($k, $v, &$data): void
    {
        $data === null && $data = [];
        $count = count($k);
        $groups = &$data;
        foreach ($k as $i => $group) {
            if ($i + 1 < $count) {
                isset($groups[$group]) || $groups[$group] = [];
                $groups = &$groups[$group];
            } else if ($group === null) {
                $groups[] = $v;
            } else {
                $groups[$group] = $v;
            }
        }
    }

    public function getIterator(): Generator
    {
        yield from $this::map($this->closure, ...$this->iterables);
    }
}
