<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php {

    use RuntimeException;

    /**
     * @param string $message
     * @param string ...$replacements
     */
    function fail($message, ...$replacements): void
    {
        Functions::fail(RuntimeException::class, $message, ...$replacements);
    }
}

namespace Php\fail {

    use Php\_;

    use DomainException;
    use InvalidArgumentException;
    use LogicException;
    use OutOfRangeException;
    use Php\Functions;

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function logic($message, ...$replacements): void
    {
        Functions::fail(LogicException::class, $message, ...$replacements);
    }

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function argument($message, ...$replacements): void
    {
        Functions::fail(InvalidArgumentException::class, $message, ...$replacements);
    }

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function range($message, ...$replacements): void
    {
        Functions::fail(OutOfRangeException::class, $message, ...$replacements);
    }

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function domain($message, ...$replacements): void
    {
        Functions::fail(DomainException::class, $message, ...$replacements);
    }
}
