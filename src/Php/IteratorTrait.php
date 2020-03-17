<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Generator;
use Iterator;

/**
 * @see Iterator
 */
trait IteratorTrait
{
    protected $iterState = [];

    protected function getInnerIterator(): Iterator
    {
        return $this->iterState['inner'];
    }

    protected function callIteratorMethod(string $method)
    {
        if (!($this->iterState['isReset'] ?? false)) {
            $this->rewind();
        }
        return $this->iterState['cache'][$method] ?? $this->getInnerIterator()->$method();
    }

    public function rewind(): void
    {
        $this->iterState['isReset'] = true;
        unset($this->iterState['cache']);

        if (($this->iterState['inner'] ?? null) instanceof Generator) {
            $this->iterState['inner'] = null;
        }
        $this->getInnerIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->callIteratorMethod(__FUNCTION__);
    }

    public function current()
    {
        return $this->callIteratorMethod(__FUNCTION__);
    }

    public function key()
    {
        return $this->callIteratorMethod(__FUNCTION__);
    }

    public function next(): void
    {
        if (isset($this->iterState['cache'])) {
            unset($this->iterState['cache']);
        } else {
            $this->getInnerIterator()->next();
        }
    }

    public function isLast(): ?bool
    {
        if (!($this->iterState['isReset'] ?? false)) {
            return null;
        }

        if (!isset($this->iterState['cache'])) {
            $this->iterState['cache'] = [
                'valid'   => $this->iterState['inner']->valid(),
                'key'     => $this->iterState['inner']->key(),
                'current' => $this->iterState['inner']->current(),
            ];

            $this->iterState['inner']->valid() && $this->iterState['inner']->next();
        }
        return $this->iterState['cache']['valid'] ? !$this->iterState['inner']->valid() : null;
    }
}
