<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php {

    use RuntimeException;

    /**
     * @param string $message
     * @param string ...$replacements
     */
    function fail($message, ...$replacements): void
    {
        _\fail(RuntimeException::class, $message, ...$replacements);
    }
}

namespace php\fail {

    use php\_;

    use DomainException;
    use InvalidArgumentException;
    use LogicException;
    use OutOfBoundsException;
    use OutOfRangeException;
    use UnexpectedValueException;

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function logic($message, ...$replacements): void
    {
        _\fail(LogicException::class, $message, ...$replacements);
    }

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function argument($message, ...$replacements): void
    {
        _\fail(InvalidArgumentException::class, $message, ...$replacements);
    }

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function range($message, ...$replacements): void
    {
        _\fail(OutOfRangeException::class, $message, ...$replacements);
    }

    /**
     * @param $message
     * @param mixed ...$replacements
     */
    function domain($message, ...$replacements): void
    {
        _\fail(DomainException::class, $message, ...$replacements);
    }
}
