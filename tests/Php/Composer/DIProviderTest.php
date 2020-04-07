<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use Php;
use PHPUnit\Framework\TestCase;

class DIProviderTest extends TestCase
{
    public function providerGetIterator(): array
    {
        return [
            'empty' => [
                'expected' => [new DIRenderer(DI::class, [], [], [], [])],
                'di' => [],
                'config' => []
            ],
            'complex' => [
                'expected' => [
                    new DIRenderer(
                        DI::class,
                        [Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,],
                        ['ns\c1', 'ns\c5'],
                        [],
                        ['foo' => 'bar', 'bar' => 'foo', 'baz' => ['foo', 'bar']]
                    ),
                    new DIRenderer('ns\c1', ['cache' => true, Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,], ['ns\c2', 'ns\c3']),
                    new DIRenderer('ns\c2', [Php\DI\ContainerConfiguration::WIRING => false], [], ['config/c2.php']),
                    new DIRenderer(
                        'ns\c3',
                        [Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,],
                        ['ns\c4'],
                        ['config/c31.php', 'config/c32.php'],
                        ['foo' => 'bar', 'bar' => ['foo' => ['a', 'b']]]
                    ),
                    new DIRenderer('ns\c4', [Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,], [], ['config/c4.php']),
                    new DIRenderer(
                        'ns\c5',
                        ['cast-to-array', Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,],
                        ['ns\c4'],
                        ['config/c5.php']
                    )
                ],
                'di' => [
                    'foo' => 'bar',
                    '@ns\c1' => [
                        '@ns\c2' => 'config/c2.php',
                        '@ns\c3' => [
                            'config/c31.php',
                            'foo' => 'bar',
                            'config/c32.php',
                            '@ns\c4' => 'config/c4.php',
                            'bar' => [
                                'foo' => ['a', 'b']
                            ],
                        ],
                    ],
                    'bar' => 'foo',
                    '@ns\c5' => [
                        '@ns\c4',
                        'config/c5.php',
                    ],
                    'baz' => ['foo', 'bar'],
                ],
                'config' => [
                    Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,
                    '@ns\c5' => 'cast-to-array',
                    '@ns\c1' => ['cache' => true],
                    '@ns\c2' => [Php\DI\ContainerConfiguration::WIRING => false],
                ]
            ],
        ];
    }

    /**
     * @dataProvider providerGetIterator
     *
     * @param array $expected
     * @param array $di
     * @param array $config
     */
    public function testGetIterator(array $expected, array $di, array $config): void
    {
        $actual = [];
        foreach (new DIProvider($di, $config) as $renderer) {
            $actual[] = $renderer;
        }
        self::assertEquals($expected, $actual);
    }
}
