<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayObject;
use Php\test\assert;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testToTraversable(): void
    {
        $ar = [true];
        $it = new ArrayObject($ar);
        assert\same($ar, Functions::toTraversable([true]));
        assert\same($it, Functions::toTraversable($it));
        assert\equals($it, Functions::toTraversable(new ArrayObject($ar)));
        assert\not\same($it, Functions::toTraversable(new ArrayObject($ar)));
        assert\same(['string'], Functions::toTraversable('string', true));
        assert\same([], Functions::toTraversable(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            Functions::toTraversable('string');
        });
    }

    public function testToArray(): void
    {
        assert\equals(['key' => 'value'], Functions::toArray(['key' => 'value']));
        assert\equals(['key' => 'value'], Functions::toArray(new ArrayObject(['key' => 'value'])));
        assert\equals([], Functions::toArray(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            Functions::toArray(null);
        });
    }

    public function testToValues(): void
    {
        assert\equals(['value'], Functions::toValues(['key' => 'value']));
        assert\equals(['value'], Functions::toValues(new ArrayObject(['key' => 'value'])));
        assert\equals([], Functions::toValues(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            Functions::toValues(null);
        });
    }

    /**
     * @dataProvider providerStr
     *
     * @param string $expected
     * @param string $subject
     * @param array $replacements
     */
    public function testStr($expected, $subject, ...$replacements): void
    {
        assert\same($expected, Php::str($subject, ...$replacements));
    }

    public function providerStr(): array
    {
        return [
            '{0 %s %d  | format' => [
                '{0 string 7 111',
                '{0 %s %d %b',
                'string', 7, 7
            ],
            '{0}{unknown}{merged} %s {1} {orig} {2} | merged replace' => [
                'zero{unknown}RENAMED %s {1} ORIG two',
                '{0}{unknown}{merged} %s {1} {orig} {2}',
                'zero', ['merged' => 'NAMED', 'orig' => 'ORIG'], 'two', ['merged' => 'RENAMED']
            ],
            '{0} | replace without args' => ['{0}', '{0}'],
            '%s | format without args' => ['%s', '%s'],
            'null | with args' => ['', null, 'arg'],
            'null' => ['', null],
        ];
    }
}