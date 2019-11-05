<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

/**
 */
trait ArrayAccessTrait
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @return array
     */
    private function data(): array
    {
        return is_array($this->data) ? $this->data : $this->data = [];
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return hasKey($offset, $this->data());
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        hasKey($offset, $data = $this->data()) || fail\argument($offset);
        return $data[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->data();
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        $this->data();
        unset($this->data[$offset]);
    }
}
