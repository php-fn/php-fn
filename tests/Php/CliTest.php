<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CliTest extends TestCase
{
    public function testDiNested(): void
    {
        $inner = Php::di([
            stdClass::class => function () {
                return (object)['foo' => 'bar'];
            }
        ]);

        $cli = new Cli(Cli::di(
            function () {
                yield 'foo' => function (stdClass $obj) {
                    yield ((array)$obj)['foo'] ?? 'ERROR';
                };
            },
            $inner
        ));
        $cli->setDefaultCommand('foo', true);
        $cli->setAutoExit(false);
        $cli->run(new ArrayInput([]), $out = new BufferedOutput());
        self::assertStringStartsWith('bar', $out->fetch());
    }

    public function testDi(): void
    {
        $package = Package::get(VENDOR\PHP_FN\PHP_FN);
        self::assertInstanceOf(Cli::class, $cli = new Cli(Cli::di(VENDOR\PHP_FN\PHP_FN)));
        self::assertSame($package->name, $cli->getName());
        self::assertSame($package->version(), $cli->getVersion());

        $cli = new Cli(Cli::di($package, ['foo' => 'bar'], static function (DI\Container $di, Package $package) {
            $cli = $di->get(Cli::class);
            $cli->command('c1', static function () {});
            yield 'c2' => static function () {};
            yield 'c3' => require $package->file('tests/fixtures/command.php');
            yield 'c4' => [require $package->file(__DIR__ . '/../fixtures/command.php'), ['arg']];
        }));
        self::assertTrue($cli->has('c1'));
        self::assertTrue($cli->has('c2'));
        self::assertTrue($cli->has('c3'));
        self::assertTrue($cli->has('c4'));
        self::assertSame('command', $cli->get('c3')->getDescription());
        self::assertSame(0, $cli->get('c3')->getDefinition()->getArgumentCount());
        self::assertSame(1, $cli->get('c4')->getDefinition()->getArgumentCount());
        self::assertSame('foo', (new Cli(Cli::di(['cli.name' => 'foo'])))->getName());
    }
}
