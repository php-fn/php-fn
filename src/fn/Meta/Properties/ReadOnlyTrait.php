<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Meta\Properties;

use fn;

/**
 */
trait ReadOnlyTrait
{
    use ReadWriteTrait;

    /**
     * @inheritdoc
     */
    public function __set($property, $value)
    {
        fn\fail('class %s has read-only access for magic-properties: %s', get_class($this), $property);
    }

    /**
     * @inheritdoc
     */
    public function __unset($property)
    {
        fn\fail('class %s has read-only access for magic-properties: %s', get_class($this), $property);
    }
}
