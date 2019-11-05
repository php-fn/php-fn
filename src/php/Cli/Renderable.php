<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\Cli;

use php;

/**
 * @todo add unit tests
 */
class Renderable
{
    /**
     * @var mixed
     */
    private $content;

    /**
     * @var int
     */
    private $type;

    /**
     * @param mixed $content
     * @param int $type
     */
    public function __construct($content, int $type = IO::OUTPUT_NORMAL)
    {
        $this->content = $content;
        $this->type = $type;
    }

    /**
     * @param IO $io
     *
     * @return int
     */
    public function toCli(IO $io): int
    {
        return static::render($io, $this->type, $this->content);
    }

    /**
     * @param IO $io
     * @param int $type
     * @param $content
     * @return int
     */
    protected static function render(IO $io, int $type, $content): int
    {
        if ((($type & IO::VERBOSITY) ?: IO::VERBOSITY_NORMAL) > $io->getVerbosity()) {
            return 0;
        }

        if ($content instanceof self) {
            return $content->toCli($io);
        }

        if (php\isCallable($content)) {
            return (int)$content($io, $type);
        }

        if (is_array($content)) {
            $current = current($content);
            if (is_array($current)) {
                $io->table(php\keys($current), $content);
            } else {
                $io->listing($content);
            }
            return count($content);
        }

        if (is_iterable($content)) {
            $count = 0;
            foreach ($content as $key => $line) {
                is_string($key) && $io->title($key);
                $count += static::render($io, $type, $line);
            }
            return $count;
        }

        if (method_exists($content, '__toString')) {
            $content = (string)$content;
        }

        if (is_object($content) && ($content = json_encode($content, JSON_PRETTY_PRINT)) === false) {
            if ($io->getVerbosity() >= IO::VERBOSITY_DEBUG) {
                $io->error(json_last_error_msg());
            }
            return 0;
        }
        $io->writeln($content, $type);
        return 1;
    }
}
