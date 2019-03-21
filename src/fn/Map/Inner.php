<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Map;

use fn;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use RecursiveArrayIterator;
use Traversable;

/**
 */
class Inner extends IteratorIterator
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var bool
     */
    private $isReset;

    /**
     * @param traversable|array $inner
     */
    public function __construct($inner)
    {
        parent::__construct(self::unify($inner));
    }

    /**
     * @param traversable|array $inner
     *
     * @return RecursiveArrayIterator|Traversable
     */
    private static function unify($inner)
    {
        if ($inner instanceof Iterator) {
            return $inner;
        }

        if (is_array($inner)) {
            return new RecursiveArrayIterator($inner);
        }

        $counter = 0;
        while ($inner instanceof IteratorAggregate) {
            $counter++ > 10 && fn\fail('$inner::getIterator is too deep');
            if (($temp = $inner->getIterator()) === $inner) {
                fn\fail('Implementation $inner::getIterator returns same instance');
            }
            $inner = $temp;
        }

        if ($inner instanceof Traversable && !$inner instanceof Iterator) {
            return $inner;
        }

        $inner instanceof Iterator || fn\fail('Property $inner must be iterable');
        return $inner;
    }

    /**
     * @return bool|null
     */
    public function isLast()
    {
        if (!$this->isReset) {
            return null;
        }

        if (!$this->cache) {
            $this->cache = [
                'valid'   => parent::valid(),
                'key'     => parent::key(),
                'current' => parent::current(),
            ];

            parent::valid() && parent::next();
        }
        return $this->cache['valid'] ? !parent::valid() : null;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->isReset = true;
        $this->cache   = [];
        parent::rewind();
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->cache ? $this->cache['valid'] : parent::valid();
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->cache ? $this->cache['current'] : parent::current();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->cache ? $this->cache['key'] : parent::key();
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->cache ? $this->cache = [] : parent::next();
    }
}
