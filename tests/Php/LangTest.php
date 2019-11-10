<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Php\test\assert;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    public function testSanitize(): void
    {
        assert\same('Foo', Lang::sanitize('Foo'));
        assert\same('Foo', Lang::sanitize('Foo', false));
        assert\same('And_', Lang::sanitize('And'));
        assert\same('And_', Lang::sanitize('And', false));
        assert\same('Numeric_', Lang::sanitize('Numeric'));
        assert\same('Numeric', Lang::sanitize('Numeric', false));
        assert\same('Numeric-', Lang::sanitize('Numeric', true, '-'));
    }
}
