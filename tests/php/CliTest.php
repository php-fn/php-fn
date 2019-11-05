<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Php\test\assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass Cli
 */
class CliTest extends TestCase
{
    /**
     * @covers \Php\Cli::command
     *
     * @todo complete
     */
    public function testCommand(): void
    {
        $cli = new Cli(DI::create());
        assert\type(Command::class, $cli->command('cmd', static function () {}));
    }

    /**
     * @covers \Php\Cli::fromPackage
     * @uses \Php\cli
     */
    public function testFromPackage(): void
    {
        $package = Package::get(VENDOR\PHP_FN\PHP_FN);
        assert\type(Cli::class, $cli = Cli::fromPackage(VENDOR\PHP_FN\PHP_FN));
        assert\same($package->name, $cli->getName());
        assert\same($package->version(), $cli->getVersion());

        $cli = Cli::fromPackage($package, ['foo' => 'bar'], static function (DI\Container $di, Package $package) {
            $cli = $di->get(Cli::class);
            $cli->command('c1', static function () {});
            yield 'c2' => static function () {};
            yield 'c3' => require $package->file('tests/fixtures/command.php');
            yield 'c4' => [require $package->file(__DIR__ . '/../fixtures/command.php'), ['arg']];
        });
        assert\true($cli->has('c1'));
        assert\true($cli->has('c2'));
        assert\true($cli->has('c3'));
        assert\true($cli->has('c4'));
        assert\same('command', $cli->get('c3')->getDescription());
        assert\same(0, $cli->get('c3')->getDefinition()->getArgumentCount());
        assert\same(1, $cli->get('c4')->getDefinition()->getArgumentCount());
        assert\same('foo', Cli::fromPackage(['cli.name' => 'foo'])->getName());
    }
}
