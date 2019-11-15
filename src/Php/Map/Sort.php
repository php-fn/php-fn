<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use ArrayIterator;
use IteratorAggregate;
use Php;

class Sort implements IteratorAggregate
{
    public const REGULAR = SORT_REGULAR;             // 0b000000
    public const NUMERIC = SORT_NUMERIC;             // 0b000001
    public const STRING = SORT_STRING;               // 0b000010
    public const LOCALE_STRING = SORT_LOCALE_STRING; // 0b000101
    public const NATURAL = SORT_NATURAL;             // 0b000110
    public const FLAG_CASE = SORT_FLAG_CASE;         // 0b001000
    public const KEYS                                 = 0b010000;
    public const REVERSE                              = 0b100000;

    private $iterable;
    private $strategy;
    private $flags;
    private $byKeys;
    private $reverse;

    /**
     * @param iterable $iterable
     * @param callable|int $strategy callable or SORT_ constants
     * @param int $flags SORT_ constants
     */
    public function __construct($iterable, $strategy = null, $flags = null)
    {
        $this->iterable = $iterable;
        if (Php::isCallable($strategy)) {
            $this->strategy = $strategy;
        } else {
            $flags = $strategy | $flags;
        }
        $this->flags = 0b001111 & $flags;
        $this->byKeys = (bool)(self::KEYS & $flags);
        $this->reverse = (bool)(self::REVERSE & $flags);
    }

    private function sortCallable(): array
    {
        $compare = $this->reverse ? function($left, $right) {
            return call_user_func($this->strategy, $right, $left);
        } : $this->strategy;
        $array = Php::traverse($this->iterable);
        $this->byKeys ? uksort($array, $compare) : uasort($array, $compare);
        return $array;
    }

    private function sortFlags(): array
    {
        $array = Php::traverse($this->iterable);
        if ($this->reverse) {
            $this->byKeys ? krsort($array, $this->flags) : arsort($array, $this->flags);
        } else {
            $this->byKeys ? ksort($array, $this->flags) : asort($array, $this->flags);
        }
        return $array;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->strategy ? $this->sortCallable() : $this->sortFlags());
    }
}
