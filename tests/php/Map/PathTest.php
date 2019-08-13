<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\Map;

use php;
use php\test\assert;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Path
 */
class PathTest extends TestCase
{
    /**
     */
    public function testFlatten(): void
    {
        $iterable = ['a', ['b', 'c', ['d', ['e'], 'f']], 'g'];
        $expected = [
            'a',
            ['b', 'c', ['d', ['e'], 'f']],
            '1/0' => 'b',
            '1/1' => 'c',
            '1/2' => ['d', ['e'], 'f'],
            '1/2/0' => 'd',
            '1/2/1' => ['e'],
            '1/2/1/0' => 'e',
            '1/2/2' => 'f',
            'g'
        ];

        assert\same($expected, php\map($iterable)->flatten()->traverse);
        assert\same($expected, php\map($iterable)->flatten);
        assert\same($expected, php\flatten($iterable));
        assert\same([
            'a',
            ['b'],
            '1/0' => 'b',
            ['c', 'd'],
            '2/0' => 'c',
            '2/1' => 'd',
            'e',
        ], php\flatten(['a', ['b']], [['c', 'd']], ['e']));

        assert\same([
            '0 depth=0' => 'a',
            '1 depth=0' => 'b',
            '2 depth=0' => ['c', ['d']],
            '2/0 depth=1' => 'c',
            '2/1 depth=1' => ['d'],
            '2/1/0 depth=2' => 'd',
            '3 depth=0' => 'e',
        ], php\flatten(['a', 'b', ['c', ['d']], 'e'], static function ($value, &$key, Path $it) {
            $key .= ' depth=' . $it->getDepth();
            return $value;
        }));

        assert\same([
            ['a', 'b'],
            '0-0' => 'a',
            '0-1' => 'b',
        ], php\map([['a', 'b']])->flatten(null, '-')->traverse);

        assert\equals([
            'a',
            2       => [['c', 'd']],
            '2/0'   => ['c', 'd'],
            '2/0/0' => 'C',
            '-'     => 'D',
        ], php\flatten(['a', ['b'], [['c', 'd']]], static function ($value, $key, Path $it) {
            if (strpos($key, '1') === 0) {
                return null;
            }
            if ($it->getDepth() === 2) {
                $value = php\mapValue(strtoupper($value));
                $key === '2/0/1' && $value->andKey('-');
            }
            return $value;
        }));
    }
}
