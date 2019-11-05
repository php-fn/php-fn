<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php;

/**
 * Class S1
 *
 * @package php
 */
class S1
{
    /**
     * Command S1::__invoke
     *
     * @param Cli\IO $io
     * @param bool $flag
     */
    public function __invoke(Cli\IO $io, bool $flag = false)
    {
        $flag ? $io->success('true') : $io->error('false');
    }
}
