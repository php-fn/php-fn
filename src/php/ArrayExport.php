<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Convert array to a short index format
 */
class ArrayExport
{
    /**
     * @var array
     */
    private $iterable;

    /**
     * @param array $iterable
     */
    public function __construct(array $iterable)
    {
        $this->iterable = $iterable;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $tokens = ['['];

        $it = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->iterable),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $depths = [$init = ['i' => 0, 'c' => 0]];
        foreach ($it as $key => $value) {
            $depth = $it->getDepth();
            $lastDepth = \count($depths) - 1;
            $depths[$depth] = $depths[$depth] ?? $init;
            if ($depth > $lastDepth) {
                $tokens[] = '[';
            } else if ($depth < $lastDepth) {
                $depths = \array_slice($depths, 0, $depth + 1, true);
                $tokens[] = \str_repeat(']', $lastDepth - $depth);
                $tokens[] = ', ';
            } else if ($depths[$depth]['c']) {
                $tokens[] = ', ';
            }

            if (!\is_numeric($key)) {
                $tokens[] = var_export($key, true);
                $tokens[] = ' => ';
            } else if ($key === $depths[$depth]['i']) {
                $depths[$depth]['i']++;
            } else {
                $depths[$depth]['i'] = max($key + 1, $depths[$depth]['i']);
                $tokens[] = var_export($key, true);
                $tokens[] = ' => ';
            }

            if (!\is_array($value)) {
                $tokens[] = var_export($value, true);
            } else if (empty($value)) {
                $tokens[] = '[]';
            }

            $depths[$depth]['c']++;
        }

        $tokens[] = ']';
        $tokens[] = \str_repeat(']', \count($depths) - 1);

        return implode('', $tokens);
    }
}
