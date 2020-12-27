<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use Composer;
use Php;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PluginTest extends TestCase
{
    private static $TARGET;

    public static function setUpBeforeClass(): void
    {
        $fs = new Composer\Util\Filesystem();
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
                        'di-config' => [Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION]
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
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
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
                            Php\DI\ContainerConfiguration::WIRING => Php\DI\Wiring::REFLECTION,
                            '@ns\c5' => 'cast-to-array',
                            '@ns\c1' => ['cache' => true],
                            '@ns\c2' => [Php\DI\ContainerConfiguration::WIRING => false],
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @large
     *
     * @dataProvider providerOnAutoloadDump
     *
     * @param mixed $expected
     * @param array $config
     */
    public function testOnAutoloadDump($expected, array $config): void
    {
        $vendorDir = dirname((new ReflectionClass(Composer\Autoload\ClassLoader::class))->getFileName(), 2);
        (new Composer\Util\Filesystem)->copy(
            dirname(__DIR__, 2) . "/fixtures/{$this->dataName()}",
            self::target($this->dataName())
        );
        $cwd = dirname($this->jsonFile($config));
        $executor = new Composer\Util\ProcessExecutor();
        $output = '';
        self::assertEquals(
            0,
            $executor->execute($vendorDir . '/bin/composer install --prefer-dist --no-dev', $output, $cwd)
        );
        self::assertEquals("vendor/autoload.php' modified\n", substr($output, -30), $output);
        $executor->execute('php -d apc.enable_cli=1 test.php', $output, $cwd);
        self::assertEquals('', $executor->getErrorOutput());
        self::assertEquals($expected, $output);
    }

    private function jsonFile(array $config): string
    {
        $selfPath = dirname(__DIR__, 3);

        $jsonFile = self::target($this->dataName(), 'composer.json');
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
