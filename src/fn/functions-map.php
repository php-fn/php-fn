<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\map;

use fn;

/**
 * @param mixed $value
 * @param mixed $key
 * @param mixed $children
 * @return Value
 */
function value(...$args)
{
    return new fn\Map\Value(...$args);
}

/**
 * @param mixed $key
 * @return Value
 */
function key($key)
{
    return value()->andKey($key);
}

/**
 * Returned object is used to mark the value as NULL in the @see \fn\traverse() function,
 * since NULL itself is used to filter/skip values
 *
 * @return Value
 */
function null()
{
    static $null;
    if (!$null) {
        $null = value();
    }
    return $null;
}

/**
 * Returned object is used to stop the iteration in the @see \fn\traverse() function
 *
 * @return Value
 */
function stop()
{
    static $stop;
    if (!$stop) {
        $stop = value();
    }
    return $stop;
}

/**
 * @param iterable|callable $children
 * @return Value
 */
function children($children)
{
    return value()->andChildren($children);
}
