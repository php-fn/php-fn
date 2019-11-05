<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use Php;
use Php\test\assert;
use Composer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass DIPlugin
 */
class DIPluginTest extends TestCase
{
    private static $TARGET;

    public static function setUpBeforeClass()
    {
        $fs = new Composer\Util\Filesystem;
        $fs->ensureDirectoryExists(self::$TARGET = sys_get_temp_dir() . '/php-fn-di-' . md5(microtime()) . '/');
    }

    private static function target(string ...$path): string
    {
        return self::$TARGET . implode('/', $path);
    }

    public static function providerOnAutoloadDump(): array
    {
        return [
            'extra-empty'  => [DI::class, ['extra' => []]],
            'extra-string' => ['di.php', ['name' => 'php-fn/extra-string', 'extra' => ['di' => 'config/di.php']]],
            'extra-string-reflection' => [
                \DI\ContainerBuilder::class,
                [
                    'name'  => 'php-fn/extra-string-reflection',
                    'extra' => [
                        'di'        => 'config/di.php',
                        'di-config' => [Php\DI::WIRING => Php\DI\WIRING::REFLECTION]
                    ]
                ]
            ],
            'extra-array' => [
                json_encode([
                    'invoker-value' => 'foo',
                    'c2-file' => 'C2',
                    'c31-file' => 'C31',
                    'c32-file' => 'C32',
                    'c3-value' => ['foo' => ['a', 'b']],
                    'c4-file' => 'C4',
                    'c5-file' => 'C5',
                    'base-dir' => '/extra-array/',
                    'vendor-dir' => '/extra-array/vendor/php-di/php-di/',
                ], JSON_PRETTY_PRINT),
                [
                    'name'  => 'php-fn/extra-array',
                    'extra' => [
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
                        'di-config' => [
                            Php\DI::WIRING => Php\DI\WIRING::REFLECTION,
                            '@ns\c5' => 'cast-to-array',
                            '@ns\c1' => ['cache' => true],
                            '@ns\c2' => [Php\DI::WIRING => false],
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @large
     *
     * @covers \Php\Composer\DIPlugin::onAutoloadDump
     * @dataProvider providerOnAutoloadDump
     *
     * @param mixed $expected
     * @param array $config
     */
    public function testOnAutoloadDump($expected, array $config): void
    {
        (new Composer\Util\Filesystem)->copy(
            dirname(__DIR__, 2) . "/fixtures/{$this->dataDescription()}",
            self::target($this->dataDescription())
        );
        $cwd = dirname($this->jsonFile($config));

        $executor = new Composer\Util\ProcessExecutor;
        $output = '';
        $executor->execute(__DIR__ . '/../../../vendor/bin/composer install --prefer-dist --no-dev', $output, $cwd);
        assert\equals("vendor/autoload.php' modified\n", substr($output, -30), $output);
        $executor->execute('php -d apc.enable_cli=1 test.php', $output, $cwd);
        assert\equals('', $executor->getErrorOutput());
        assert\equals($expected, $output);
    }

    private function jsonFile(array $config): string
    {
        $selfPath = dirname(__DIR__, 3);

        $jsonFile = self::target($this->dataDescription(), 'composer.json');
        /** @noinspection PhpUnhandledExceptionInspection */
        (new Composer\Json\JsonFile($jsonFile))->write($config + [
            'require'      => ['php-fn/php-fn' => '999'],
            'minimum-stability' => 'dev',
            'config'       => ['cache-dir' => '/dev/null', 'data-dir' => '/dev/null'],
            'repositories' => [[
                'type' => 'package',
                'package' => array_merge(
                    (new Composer\Json\JsonFile($selfPath . '/composer.json'))->read(),
                    ['version' => '999', 'dist' => ['type' => 'path', 'url' => $selfPath]]
                ),
            ]],
        ]);

        return $jsonFile;
    }
}
