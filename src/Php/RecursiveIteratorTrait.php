<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use EmptyIterator;
use Generator;
use Iterator;
use IteratorAggregate;
use RecursiveIterator;
use Traversable;

class RecursiveIteratorTrait implements RecursiveIterator
{
    use IteratorTrait;

    public static function foo()
    {
        Php::arr([], function ($value, $key) {
            yield [$value['parent'] ?? null, $key] => $value;
        });

        new RecursiveIteratorTrait(['a', 'b'], static function () {

        });
    }

    protected function getChildrenIterator()
    {
        if (!$this->valid()) {
            return null;
        }
        return new EmptyIterator;
    }

    public function hasChildren(): bool
    {
        if (!$this->valid()) {
            return false;
        }
        if (!is_iterable($iter = $this->getChildrenIterator())) {
            return false;
        }
        if (is_array($iter)) {
            return (bool)$iter;
        }
        if ($iter instanceof IteratorAggregate) {
            $iter = $iter->getIterator();
        }
        if ($iter instanceof Iterator) {
            return $iter->valid();
        }
        if ($iter instanceof Traversable) {
            return iterator_count($iter) > 0;
        }
        return false;
    }



    public function getChildren()
    {

    }
}
