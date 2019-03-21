<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use ArrayAccess;
use Closure;
use fn;

/**
 */
class RowMapper
{
    /**
     * @var Value
     */
    private $config;

    /**
     * @param string|Closure          $key
     * @param string|iterable|Closure $value
     * @param string                  ...$group
     */
    public function __construct($key, $value = null, ...$group)
    {
        $this->config = new Value($value, $key, $group);
    }

    /**
     * @param array|ArrayAccess|iterable $row
     * @param mixed                      $key
     *
     * @return Value
     */
    public function __invoke($row, $key)
    {
        if (!(is_array($row) || $row instanceof ArrayAccess)) {
            fn\_\isTraversable($row) ?: fn\fail\domain('row should be of type: array|ArrayAccess|iterable');
            $row = fn\_\toArray($row);
        }

        $rowValues = fn\map($row)->values();
        $mapped = new Value;

        if (null !== $valueColumns = $this->config->value) {
            if ($valueColumns instanceof Closure) {
                $mappedValue = $valueColumns($row, $key, $mapped);
            } else if (fn\_\isTraversable($valueColumns)) {
                $mappedValue = [];
                foreach ($valueColumns as $toColumn => $fromColumn) {
                    if (is_numeric($toColumn)) {
                        $toColumn = $fromColumn;
                    }
                    if (is_int($fromColumn)) {
                        $mappedValue[] =  fn\at($fromColumn, $rowValues );
                    } else {
                        $mappedValue[$toColumn] =  fn\at($fromColumn, $row);
                    }
                }
            } else {
                $mappedValue = fn\at($valueColumns, is_int($valueColumns) ? $rowValues : $row);
            }
            $mapped->andValue($mappedValue);
        }

        if (null !== $keyColumn = $this->config->key) {
            if ($keyColumn instanceof Closure) {
                $mappedKey = $keyColumn($row, $key, $mapped);
            } else {
                $mappedKey = fn\at($keyColumn, is_int($keyColumn) ? $rowValues : $row);
            }
            $mapped->andKey($mappedKey);
        }

        if ($groupColumns = $this->config->group) {
            $mappedGroups = [];
            foreach ($groupColumns as $groupColumn) {
                $mappedGroups[] = fn\at($groupColumn, is_int($groupColumn) ? $rowValues : $row);
            }
            $mapped->andGroup($mappedGroups);
        }

        return $mapped;
    }
}
