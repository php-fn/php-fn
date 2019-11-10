<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

abstract class Lang
{
    /**
     * https://www.php.net/manual/en/reserved.other-reserved-words.php
     */
    public const RESERVED_WORDS = [
        '__halt_compiler' => false,
        'abstract' => false,
        'and' => false,
        'array' => false,
        'as' => false,
        'break' => false,
        'callable' => false,
        'case' => false,
        'catch' => false,
        'class' => false,
        'clone' => false,
        'const' => false,
        'continue' => false,
        'declare' => false,
        'default' => false,
        'die' => false,
        'do' => false,
        'echo' => false,
        'else' => false,
        'elseif' => false,
        'empty' => false,
        'enddeclare' => false,
        'endfor' => false,
        'endforeach' => false,
        'endif' => false,
        'endswitch' => false,
        'endwhile' => false,
        'eval' => false,
        'exit' => false,
        'extends' => false,
        'final' => false,
        'fn' => false,
        'for' => false,
        'foreach' => false,
        'function' => false,
        'global' => false,
        'goto' => false,
        'if' => false,
        'implements' => false,
        'include' => false,
        'include_once' => false,
        'instanceof' => false,
        'insteadof' => false,
        'interface' => false,
        'isset' => false,
        'list' => false,
        'namespace' => false,
        'new' => false,
        'or' => false,
        'print' => false,
        'private' => false,
        'protected' => false,
        'public' => false,
        'require' => false,
        'require_once' => false,
        'return' => false,
        'static' => false,
        'switch' => false,
        'throw' => false,
        'trait' => false,
        'try' => false,
        'unset' => false,
        'use' => false,
        'var' => false,
        'while' => false,
        'xor' => false,
        'int' => true,
        'float' => true,
        'bool' => true,
        'string' => true,
        'true' => true,
        'false' => true,
        'null' => true,
        'void' => true,
        'iterable' => true,
        'object' => true,
        'resource' => true,
        'mixed' => true,
        'numeric' => true,
    ];

    public static function sanitize($word, bool $strict = true, string $suffix = '_'): string
    {
        if (($member = self::RESERVED_WORDS[strtolower($word)] ?? null) !== null) {
            return $member && !$strict ? $word : $word . $suffix;
        }
        return $word;
    }
}
