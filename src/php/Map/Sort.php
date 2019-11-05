<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use function Php\isCallable;
use function Php\traverse;
use ArrayIterator;
use IteratorAggregate;

/**
 */
class Sort implements IteratorAggregate
{
    const REGULAR = SORT_REGULAR;             // 0b000000
    const NUMERIC = SORT_NUMERIC;             // 0b000001
    const STRING = SORT_STRING;               // 0b000010
    const LOCALE_STRING = SORT_LOCALE_STRING; // 0b000101
    const NATURAL = SORT_NATURAL;             // 0b000110
    const FLAG_CASE = SORT_FLAG_CASE;         // 0b001000
    const KEYS                                 = 0b010000;
    const REVERSE                              = 0b100000;

    /**
     * @var iterable
     */
    private $iterable;

    /**
     * @var callable|int|null
     */
    private $strategy;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var bool
     */
    private $byKeys;

    /**
     * @var bool
     */
    private $reverse;

    /**
     * @param iterable $iterable
     * @param callable|int $strategy callable or SORT_ constants
     * @param int $flags SORT_ constants
     */
    public function __construct($iterable, $strategy = null, $flags = null)
    {
        $this->iterable = $iterable;
        if (isCallable($strategy)) {
            $this->strategy = $strategy;
        } else {
            $flags = $strategy | $flags;
        }
        $this->flags = 0b001111 & $flags;
        $this->byKeys = (bool)(self::KEYS & $flags);
        $this->reverse = (bool)(self::REVERSE & $flags);
    }

    /**
     * @return array
     */
    private function sortCallable(): array
    {
        $compare = $this->reverse ? function($left, $right) {
            return call_user_func($this->strategy, $right, $left);
        } : $this->strategy;
        $array = traverse($this->iterable);
        $this->byKeys ? uksort($array, $compare) : uasort($array, $compare);
        return $array;
    }

    /**
     * @return array
     */
    private function sortFlags(): array
    {
        $array = traverse($this->iterable);
        if ($this->reverse) {
            $this->byKeys ? krsort($array, $this->flags) : arsort($array, $this->flags);
        } else {
            $this->byKeys ? ksort($array, $this->flags) : asort($array, $this->flags);
        }
        return $array;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->strategy ? $this->sortCallable() : $this->sortFlags());
    }
}
