<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace fn;

use fn\test\assert;

/**
 * @covers fn()
 */
class functionsFnTest extends FnTest
{
    /**
     * @inheritdoc
     */
    protected function fn(...$arguments)
    {
        return fn(...$arguments);
    }

    /**
     * @covers fn()
     */
    public function testFn()
    {
        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            traverse(fn(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A']
            )),
            'two iterable arguments => replace'
        );

        assert\same(
            ['a' => 'A', 'b' => 'b', 'c' => 'c', 'd'],
            traverse(fn(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                fn(['c' => 'c', 'd'])
            )),
            'three iterable arguments => replace'
        );

        assert\same(
            ['k:a' => 'v:A', 'k:b' => 'v:b', 'k:c' => 'v:C'],
            traverse(fn(
                ['a' => 'a', 'b' => 'b'],
                ['c' => 'C', 'a' => 'A'],
                function($value, $key) {
                    return map\value("v:$value")->andKey("k:$key");
                }
            )),
            'last argument is callable => replace and map'
        );
    }
}
