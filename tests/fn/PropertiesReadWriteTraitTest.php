<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn;

use fn\test\assert;

class PropertiesReadWrite
{
    use PropertiesReadWriteTrait;
    private const DEFAULT = ['a' => null];
    public function __construct($properties)
    {
        $this->initProperties($properties);
    }
}

/**
 * @coversDefaultClass PropertiesReadWriteTrait
 */
class PropertiesReadWriteTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::property
     * @covers ::initProperties
     */
    public function testTrait(): void
    {
        $message = str(
            'magic properties (b,c) are not defined in fn\PropertiesReadWrite::DEFAULT',
            PropertiesReadWrite::class
        );
        assert\exception($message, function () {
            new PropertiesReadWrite(['b' => 'B', 'a' => 'A', 'c' => 'C']);
        });

        self::assertAB('A', null, new class(['a' => 'A', 'b' => null]) {
            use PropertiesReadWriteTrait;
            public function __construct($properties)
            {
                $this->initProperties($properties);
            }
        });

        self::assertAB('A', null, new class {
            use PropertiesReadWriteTrait;
            protected const DEFAULT = ['a' => 'A', 'b' => null];
            public function __construct()
            {
                $this->initProperties();
            }
        });

        self::assertAB('A', 'B', new class(['b' => 'B']) {
            use PropertiesReadWriteTrait;
            public function __construct($properties)
            {
                $this->initProperties($properties);
            }
            private const DEFAULT = ['a' => 'A', 'b' => null];
        });
    }

    private static function assertAB($a, $b, $obj): void
    {
        assert\true(isset($obj->a));
        assert\true(isset($obj->b));
        assert\false(isset($obj->c));
        assert\same($a, $obj->a);
        assert\same($b, $obj->b);
        assert\exception(str('missing magic-property c in %s', get_class($obj)), function ($obj) {
            $obj->c;
        }, $obj);
        unset($obj->a, $obj->b, $obj->c);
        assert\true(isset($obj->a));
        assert\true(isset($obj->b));
        assert\false(isset($obj->c));
        assert\same(null, $obj->a);
        assert\same(null, $obj->b);
    }
}
