<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Meta\Properties;

use RuntimeException;

/**
 */
trait ReadOnlyTrait
{
    use ReadWriteTrait;

    /**
     * @param string $property
     * @param string $value
     */
    public function __set($property, $value)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new RuntimeException(sprintf(
            'class %s has read-only access for magic-properties: %s',
            get_class($this),
            $property
        ));
    }

    /**
     * @param string $property
     */
    public function __unset($property)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new RuntimeException(sprintf(
            'class %s has read-only access for magic-properties: %s',
            get_class($this),
            $property
        ));
    }
}