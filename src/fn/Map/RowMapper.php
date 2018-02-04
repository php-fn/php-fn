<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use ArrayAccess;
use Closure;
use DomainException;
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
            if (!fn\isIterable($row)) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new DomainException('row should be of type: array|ArrayAccess|iterable');
            }
            $row = fn\toMap($row);
        }

        $rowValues = fn\map($row)->values();
        $mapped = new Value;

        if (null !== $valueColumns = $this->config->value) {
            if ($valueColumns instanceof Closure) {
                $mappedValue = $valueColumns($row, $key, $mapped);
            } else if (fn\isIterable($valueColumns)) {
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
