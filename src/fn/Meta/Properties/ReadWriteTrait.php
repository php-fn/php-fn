<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn\Meta\Properties;

use fn;
use OutOfBoundsException;
use RuntimeException;
use UnexpectedValueException;

/**
 * @property array $properties
 */
trait ReadWriteTrait
{
    /**
     * @param array $properties
     */
    public function __construct(array $properties = null)
    {
        if (!property_exists($this, 'properties')) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new RuntimeException(sprintf('missing property $properties in %s', get_class($this)));
        }
        if ($properties !== null) {
            if ($diff = implode(', ', array_diff(array_keys($properties), array_keys($this->properties)))) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new UnexpectedValueException($diff);
            }
            $this->properties = array_replace($this->properties, $properties);
        }
    }

    /**
     * @param string $name
     */
    private function assertProperty($name)
    {
        if (!fn\hasKey($name, $this->properties)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new OutOfBoundsException(sprintf('missing magic-property %s in %s', $name, get_class($this)));
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return fn\hasKey($name, $this->properties);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $this->assertProperty($name);
        return $this->properties[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->assertProperty($name);
        $this->properties[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->__set($name, null);
    }
}