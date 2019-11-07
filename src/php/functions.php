<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

/**
 * @param callable|mixed $candidate
 * @param bool $strict
 * @return bool
 */
function isCallable($candidate, $strict = true): bool
{
    return !(
        !is_callable($candidate, !$strict) ||
        ($strict && is_string($candidate) && !strpos($candidate, '::'))
    );
}

