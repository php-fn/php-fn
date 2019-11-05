<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\Cli;

use php\test\assert;
use php;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;

/**
 * @coversDefaultClass Parameter
 */
class ParameterTest extends TestCase
{
    /**
     * @return array
     */
    public function providerGetName(): array
    {
        $ref = new ReflectionFunction(function(
            $NewNASAModule,
            $aBcDeFgH,
            $AbCdEfGh,
            $aBCdEFgH,
            $lower,
            $Upper,
            $under_score,
            $und_Er_sCore,
            $__AB__cd__eF__Gh__,
            $a123B456c789d000
        ) {});

        $expected = [
            'NewNASAModule'      => 'new-nasa-module',
            'aBcDeFgH'           => 'a-bc-de-fg-h',
            'AbCdEfGh'           => 'ab-cd-ef-gh',
            'aBCdEFgH'           => 'a-b-cd-e-fg-h',
            'lower'              => 'lower',
            'Upper'              => 'upper',
            'under_score'        => 'under-score',
            'und_Er_sCore'       => 'und-er-s-core',
            '__AB__cd__eF__Gh__' => 'ab-cd-e-f-gh',
            'a123B456c789d000'   => 'a123-b456-c789-d000',
        ];

        return php\traverse($ref->getParameters(), function(ReflectionParameter $param, &$key) use($expected) {
            return ['expected' => $expected[$key = $param->getName()], $param];
        });
    }

    /**
     * @covers \php\Cli\Parameter::getName
     * @dataProvider providerGetName
     *
     * @param string              $expected
     * @param ReflectionParameter $ref
     */
    public function testGetName($expected, ReflectionParameter $ref): void
    {
        $param = new Parameter($ref);
        assert\same($ref->getName(), $param->getName());
        assert\same($expected, $param->getName('-'));
    }
}
