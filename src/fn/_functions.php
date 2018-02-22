<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

namespace fn\_;
use fn;
use InvalidArgumentException;

/**
 * @param array $args
 * @return callable|null
 */
function lastCallable(array &$args)
{
    if (!$args) {
        return null;
    }
    if (!fn\isIterable($last = array_pop($args)) && fn\isCallable($last, true)) {
        if (!$args) {
            throw new InvalidArgumentException('single argument should not be a callable');
        }
        return $last;
    }
    $args[] = $last;
    return null;
}
