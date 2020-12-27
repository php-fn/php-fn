<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Cli;

use Php;
use ReflectionParameter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Parameter
{
    private $ref;

    public function __construct(ReflectionParameter $ref)
    {
        $this->ref = $ref;
    }

    public function isVariadic(): bool
    {
        return $this->ref->isVariadic();
    }

    public function getName(string $delimiter = null): string
    {
        $isLow = function (string $char = null): bool {
            return $char !== null && (!ctype_upper($char) && $char !== '_');
        };

        $isUp = function (string $char = null): bool {
            return ctype_upper($char) || $char === '_';
        };

        $name = $this->ref->getName();
        if ($delimiter) {
            $tokens = [];
            $t = 0;
            foreach ($chars = str_split($name) as $i => $char) {
                $last = $chars[$i - 1] ?? null;
                $next = $chars[$i + 1] ?? null;
                $t += (int)(($isUp($char) && ($isLow($next) || $isLow($last))) || (is_numeric($next) && !is_numeric($char)));
                isset($tokens[$t]) || $tokens[$t] = '';
                $tokens[$t] .= $char;
            }

            $name = Php::map($tokens, function ($token) {
                return str_replace('_', '', strtolower($token)) ?: null;
            })->string($delimiter);
        }
        return $name;
    }

    /**
     * @param bool $asArg
     * @param string|null $desc
     *
     * @return InputOption|InputArgument
     */
    public function input(bool $asArg = false, string $desc = null)
    {
        return $asArg ? $this->arg($desc) : $this->opt($desc);
    }

    /**
     * @param string|null $desc
     *
     * @return InputArgument
     */
    private function arg(string $desc = null): InputArgument
    {
        if ($this->ref->isVariadic()) {
            $mode = InputArgument::OPTIONAL | InputArgument::IS_ARRAY;
        } else {
            $mode = $this->ref->isOptional() ? InputArgument::OPTIONAL : InputArgument::REQUIRED;
            if ($this->ref->isArray()) {
                $mode |= InputArgument::IS_ARRAY;
            }
        }

        return new InputArgument(
            $this->getName('-'),
            $mode,
            $desc,
            $this->ref->isDefaultValueAvailable() ? $this->ref->getDefaultValue() : null
        );
    }

    /**
     * @param string|null $desc
     *
     * @return InputOption
     */
    private function opt(string $desc = null): InputOption
    {
        $default = $this->ref->isDefaultValueAvailable() ? $this->ref->getDefaultValue() : null;
        isset($desc) || $desc = $this->ref->isDefaultValueAvailable() ? $this->ref->getDefaultValueConstantName() : null;
        if ($this->ref->isVariadic()) {
            $mode = InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY;
        } else if (($type = $this->ref->getType()) && $type->getName() === 'bool') {
            $mode = InputOption::VALUE_NONE;
            $default = null;
        } else {
            $mode = $this->ref->isOptional() ? InputOption::VALUE_OPTIONAL : InputOption::VALUE_REQUIRED;
            if ($this->ref->isArray()) {
                $mode |= InputOption::VALUE_IS_ARRAY;
            }
        }

        return new InputOption($this->getName('-'), null, $mode, (string)$desc, $default);
    }
}
