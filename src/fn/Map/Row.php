<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use fn;
use ArrayAccess;
use DomainException;
use LogicException;
use OutOfRangeException;

/**
 * @property-read bool $readOnly
 */
class Row extends Value implements ArrayAccess
{
    /**
     * @var string[]
     */
    const PROPERTIES = ['value', 'key', 'children', 'group', 'readOnly'];

    /**
     * @var iterable|ArrayAccess
     */
    private $row;

    /**
     * @param iterable|ArrayAccess $row
     * @param bool $readOnly
     */
    public function __construct($row, $readOnly = false)
    {
        parent::__construct();
        $this->row = $row;
        $this->properties['readOnly'] = $readOnly;
    }

    /**
     * @inheritdoc
     */
    public function __get($property)
    {
        $value = parent::__get($property);
        if (!fn\hasValue($property, parent::PROPERTIES)) {
            return $value;
        }
        if (fn\isIterable($value)) {
            return fn\traverse($value, function($offset) {
                return $this[$offset];
            });
        }
        return $value instanceof \Closure ? $value($this) : $this[$value];
    }

    /**
     * @param mixed $source
     *
     * @return bool
     */
    private static function isAccess($source)
    {
        return is_array($source) || $source instanceof ArrayAccess;
    }

    /**
     * @param string $offset
     * @param bool $check
     *
     * @return mixed
     */
    private function read($offset, $check)
    {
        if (!self::isAccess($this->row) && !fn\isIterable($this->row)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new DomainException('read access is possible only for: iterable | ArrayAccess');
        }

        if (fn\hasKey($offset, $this->row)) {
            if ($check) {
                return true;
            }
            return self::isAccess($this->row) ? $this->row[$offset] : fn\toMap($this->row)[$offset];
        }
        if ($check) {
            return false;
        }
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new OutOfRangeException("missing offset '{$offset}'");
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @param bool $unset
     */
    private function write($offset, $value, $unset)
    {
        if ($this->readOnly) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new LogicException('row has read-only access');
        }
        if (!self::isAccess($this->row)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new DomainException('write access is possible only for: array | ArrayAccess');
        }
        if ($unset) {
            unset($this->row[$offset]);
        } else {
            $this->row[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->read($offset, true);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->read($offset, false);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->write($offset, $value, false);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->write($offset, null, true);
    }
}