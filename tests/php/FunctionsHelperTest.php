<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use ArrayObject;
use function Php\_\toArray;
use function Php\_\toTraversable;
use function Php\_\toValues;
use Php\test\assert;
use PHPUnit\Framework\TestCase;

/**
 */
class FunctionsHelperTest extends TestCase
{
    /**
     */
    public function testToTraversable(): void
    {
        $ar = [true];
        $it = new ArrayObject($ar);
        assert\same($ar, toTraversable([true]));
        assert\same($it, toTraversable($it));
        assert\equals($it, toTraversable(new ArrayObject($ar)));
        assert\not\same($it, toTraversable(new ArrayObject($ar)));
        assert\same(['string'], toTraversable('string', true));
        assert\same([], toTraversable(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            toTraversable('string');
        });
    }

    /**
     */
    public function testToArray(): void
    {
        assert\equals(['key' => 'value'], toArray(['key' => 'value']));
        assert\equals(['key' => 'value'], toArray(new ArrayObject(['key' => 'value'])));
        assert\equals([], toArray(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            toArray(null);
        });
    }

    /**
     */
    public function testToValues(): void
    {
        assert\equals(['value'], toValues(['key' => 'value']));
        assert\equals(['value'], toValues(new ArrayObject(['key' => 'value'])));
        assert\equals([], toValues(null, true));
        assert\exception('argument $candidate must be traversable', static function () {
            toValues(null);
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
        assert\same($expected, str($subject, ...$replacements));
    }

    /**
     * @return array[]
     */
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
