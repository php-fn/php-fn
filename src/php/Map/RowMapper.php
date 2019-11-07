<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Map;

use ArrayAccess;
use Closure;
use Php;

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
    public function __invoke($row, $key): Value
    {
        if (!(is_array($row) || $row instanceof ArrayAccess)) {
            is_iterable($row) ?: Php::fail('row should be of type: array|ArrayAccess|iterable');
            $row = Php\Functions::toArray($row);
        }

        $rowValues = Php::map($row)->values();
        $mapped = new Value;

        if (null !== $valueColumns = $this->config->value) {
            if ($valueColumns instanceof Closure) {
                $mappedValue = $valueColumns($row, $key, $mapped);
            } else if (is_iterable($valueColumns)) {
                $mappedValue = [];
                foreach ($valueColumns as $toColumn => $fromColumn) {
                    if (is_numeric($toColumn)) {
                        $toColumn = $fromColumn;
                    }
                    if (is_int($fromColumn)) {
                        $mappedValue[] = Php::at($fromColumn, $rowValues);
                    } else {
                        $mappedValue[$toColumn] = Php::at($fromColumn, $row);
                    }
                }
            } else {
                $mappedValue = Php::at($valueColumns, is_int($valueColumns) ? $rowValues : $row);
            }
            $mapped->andValue($mappedValue);
        }

        if (null !== $keyColumn = $this->config->key) {
            if ($keyColumn instanceof Closure) {
                $mappedKey = $keyColumn($row, $key, $mapped);
            } else {
                $mappedKey = Php::at($keyColumn, is_int($keyColumn) ? $rowValues : $row);
            }
            $mapped->andKey($mappedKey);
        }

        if ($groupColumns = $this->config->group) {
            $mappedGroups = [];
            foreach ($groupColumns as $groupColumn) {
                $mappedGroups[] = Php::at($groupColumn, is_int($groupColumn) ? $rowValues : $row);
            }
            $mapped->andGroup($mappedGroups);
        }

        return $mapped;
    }
}
