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

        $cli = new Cli(Cli::di($inner));
        $cli->command('foo', function (stdClass $obj) {
            yield ((array)$obj)['foo'] ?? 'ERROR';
        });
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

        $cli = new Cli(Cli::di($package));
        $cli->command('c1', static function () {})->getDefinition()->get;
        $cli->command('c2', require $package->file('tests/fixtures/command.php'));
        self::assertTrue($cli->has('c1'));
        self::assertTrue($cli->has('c2'));
        self::assertSame('command', $cli->get('c2')->getDescription());
        self::assertSame(0, count($cli->get('c1')->getDefinition()->getOptions()));
        self::assertSame(1, count($cli->get('c2')->getDefinition()->getOptions()));
        self::assertSame('foo', (new Cli(Cli::di(['cli.name' => 'foo'])))->getName());
    }
}
