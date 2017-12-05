<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use ArrayIterator;
use IteratorAggregate;

/**
 */
class Lazy implements IteratorAggregate
{
    /**
     * @var callable
     */
    protected $factory;

    /**
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        $iter = call_user_func($this->factory);
        return is_array($iter) ? new ArrayIterator($iter) : $iter;
    }
}
