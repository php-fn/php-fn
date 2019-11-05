<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

interface WIRING
{
    public const NONE = null;
    public const AUTO = true;
    public const REFLECTION = 'reflection';
    public const STRICT = 'strict';
    public const TOLERANT = 'tolerant';
}