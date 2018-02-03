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
     * @param string                  $group
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

        $mapped = new Value;

        if ($valueColumns = $this->config->value) {
            if ($valueColumns instanceof Closure) {
                $mappedValue = $valueColumns($row, $key, $mapped);
            } else if (fn\isIterable($valueColumns)) {
                $mappedValue = [];
                foreach ($valueColumns as $toColumn => $fromColumn) {
                    if (is_numeric($toColumn)) {
                        $toColumn = $fromColumn;
                    }
                    $mappedValue[$toColumn] = fn\at($fromColumn, $row);
                }
            } else {
                $mappedValue = fn\at($valueColumns, $row);
            }
            $mapped->andValue($mappedValue);
        }

        if ($keyColumn = $this->config->key) {
            $mappedKey = $keyColumn instanceof Closure ? $keyColumn($row, $key, $mapped) : fn\at($keyColumn, $row);
            $mapped->andKey($mappedKey);
        }

        if ($groupColumns = $this->config->group) {
            $mappedGroups = [];
            foreach ($groupColumns as $groupColumn) {
                $mappedGroups[] = fn\at($groupColumn, $row);
            }
            $mapped->andGroup($mappedGroups);
        }

        return $mapped;
    }
}