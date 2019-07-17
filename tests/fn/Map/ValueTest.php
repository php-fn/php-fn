<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace fn\Map;

use fn\test\assert;
use fn;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Value
 */
class ValueTest extends TestCase
{
    public function testProperties(): void
    {
        $val = new Value;
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andValue(null));
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andKey(null));
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andGroup(null));
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        assert\same($val, $val->andChildren(null));
        assert\equals([null, null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value('v');
        assert\equals(['v', null, null, null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value(null, 'k');
        assert\equals([null, 'k', null, null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value(null, null, 'g');
        assert\equals([null, null, 'g', null], [$val->value, $val->key, $val->group, $val->children]);

        $val = new Value(null, null, null, 'c');
        assert\equals([null, null, null, 'c'], [$val->value, $val->key, $val->group, $val->children]);
    }
}
