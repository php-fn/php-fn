<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

namespace fn\test\assert {

    use PHPUnit_Framework_Assert as Assert;

    /**
     * @see Assert::assertEquals
     */
    function equals()
    {
        Assert::assertEquals(...func_get_args());
    }

    /**
     * @see Assert::assertSame
     */
    function same()
    {
        Assert::assertSame(...func_get_args());
    }

    /**
     * @see Assert::assertTrue
     */
    function true()
    {
        Assert::assertTrue(...func_get_args());
    }

    /**
     * @see Assert::assertFalse
     */
    function false()
    {
        Assert::assertFalse(...func_get_args());
    }

    /**
     * @see Assert::assertInstanceOf
     * @param string $type
     */
    function type($type)
    {
        try {
            new \PHPUnit_Framework_Constraint_IsType(strtolower($type));
        } catch (\PHPUnit_Framework_Exception $ignore) {
            Assert::assertInstanceOf(...func_get_args());
            return;
        }
        Assert::assertInternalType(strtolower($type), ...array_slice(func_get_args(), 1));
    }

    /**
     * @see Assert::fail
     */
    function fail()
    {
        Assert::fail(...func_get_args());
    }

    /**
     * @param string|\Exception $exception
     * @param callable $callable
     * @param mixed ... $arg
     */
    function exception($exception, callable $callable)
    {
        if (!$exception instanceof \Exception) {
            $exception = new \Exception($exception);
        }
        try {
            call_user_func_array($callable, array_slice(func_get_args(), 2));
            fail("Expects exception");
        } catch (\Exception $caught) {
            type(get_class($exception), $caught);
            equals($exception->getMessage(), $caught->getMessage());
        }
    }
}

namespace fn\test\assert\not {

    use PHPUnit_Framework_Assert as Assert;

    /**
     * @see Assert::assertNotEquals
     */
    function equals()
    {
        Assert::assertNotEquals(...func_get_args());
    }

    /**
     * @see Assert::assertNotSame
     */
    function same()
    {
        Assert::assertNotSame(...func_get_args());
    }

    /**
     * @see Assert::assertNotTrue
     */
    function true()
    {
        Assert::assertNotTrue(...func_get_args());
    }

    /**
     * @see Assert::assertNotFalse
     */
    function false()
    {
        Assert::assertNotFalse(...func_get_args());
    }
}

namespace fn\test\assert\equals {

    use fn\test\assert;
    use fn;

    /**
     * @see \PHPUnit_Framework_Assert::assertEquals
     *
     * @param mixed|\Exception $expected
     * @param callable $callable
     * @param mixed ... $args
     */
    function trial($expected, callable $callable)
    {
        $args = fn\sub(func_get_args(), 2);
        if ($expected instanceof \Exception) {
            assert\exception($expected, $callable, ...$args);
        } else {
            assert\equals($expected, $callable(...$args));
        }
    }
}

namespace fn\test\assert\same {

    use fn\test\assert;
    use fn;

    /**
     * @see \PHPUnit_Framework_Assert::assertSame
     *
     * @param mixed|\Exception $expected
     * @param callable $callable
     * @param mixed ... $args
     */
    function trial($expected, callable $callable)
    {
        $args = fn\sub(func_get_args(), 2);
        if ($expected instanceof \Exception) {
            assert\exception($expected, $callable, ...$args);
        } else {
            assert\same($expected, $callable(...$args));
        }
    }
}