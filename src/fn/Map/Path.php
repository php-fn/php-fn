<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use fn;
use RecursiveIteratorIterator;

/**
 * @property-read array $keys
 */
class Path extends RecursiveIteratorIterator
{
    use fn\PropertiesReadOnlyTrait;

    /**
     * @var string
     */
    public const INDENT = '  ';

    /**
     * @return array
     */
    protected function resolveKeys(): array
    {
        $depth = $this->getDepth();
        $keys  = [];
        for ($level = 0; $level <= $depth; ++$level) {
            $keys[] = $this->getSubIterator($level)->key();
        }
        return $keys;
    }

    /**
     * @param string $indent
     * @param mixed ...$current
     *
     * @return string
     */
    public function indent(string $indent = self::INDENT, ...$current): string
    {
        $current = $current[0] ?? $this->current();
        return str_repeat($indent, $depth = $this->getDepth()) . $current;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->indent();
    }
}
