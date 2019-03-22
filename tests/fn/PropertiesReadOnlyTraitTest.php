<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\test\assert;

/**
 * @coversDefaultClass PropertiesReadOnlyTrait
 */
class PropertiesReadOnlyTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::property
     * @covers ::initProperties
     */
    public function testTrait(): void
    {
        $obj = new class
        {
            use PropertiesReadOnlyTrait;

            /**
             * @param string $name
             * @param bool $assert
             * @return mixed
             */
            protected function property(string $name, bool $assert)
            {
                if (!method_exists($this, $name)) {
                    return $assert && fail($name);
                }
                return $assert ? $this->$name() : true;
            }

            protected function a(): string
            {
                return 'A';
            }
        };
        $message = str('class %s has read-only access for magic-properties: b', get_class($obj));
        assert\exception($message, function($obj) {$obj->b = null;}, $obj);
        assert\exception($message, function($obj) {unset($obj->b);}, $obj);

        assert\true(isset($obj->a));
        assert\false(isset($obj->b));
        assert\same('A', $obj->a);
        assert\exception('b', function($obj) {$obj->b;}, $obj);
    }
}
