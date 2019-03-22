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
     * @param string $name
     * @param bool $assert
     * @return mixed
     */
    protected function property(string $name, bool $assert)
    {
        if (!method_exists($this, $name)) {
            return $assert && fn\fail\domain($name);
        }
        return $assert ? $this->$name() : true;
    }

    protected function keys(): array
    {
        $depth = $this->getDepth();
        $keys  = [];
        for ($level = 0; $level <= $depth; ++$level) {
            $keys[] = $this->getSubIterator($level)->key();
        }
        return $keys;
    }
}
