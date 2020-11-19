<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace Php;

use Closure;
use DI\CompiledContainer;
use Invoker\ParameterResolver;
use Php\Cli\IO;
use Php\Cli\Parameter;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Cli extends Application
{
    /**
     * @var DI\Container
     */
    private $container;

    /**
     * @var DI\Invoker
     */
    private $invoker;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container instanceof DI\Container ? $container : Php::di($container);
        $this->container->set(self::class, $this);
        $this->container->set(static::class, $this);
        $this->invoker = $this->createInvoker($this->container);
        parent::__construct($this->value('cli.name'), $this->value('cli.version'));

        foreach ($this->getCommands() as $name => $command) {
            if (is_numeric($name) && is_string($command)) {
                $name = explode('\\', $command);
                $name = end($name);
            }
            $this->command(strtolower($name), ...array_values(is_array($command) ? $command : [$command]));
        }
    }

    protected function createInvoker(DI\Container $container): DI\Invoker
    {
        return new DI\Invoker(
            new ParameterResolver\AssociativeVariadicResolver,
            new ParameterResolver\AssociativeArrayResolver,
            new ParameterResolver\TypeHintResolver,
            $container,
            new ParameterResolver\Container\ParameterNameContainerResolver($container),
            new ParameterResolver\DefaultValueResolver
        );
    }

    private function value(string $id, $default = null)
    {
        return $this->container->has($id) ? $this->container->get($id) : $default;
    }

    /**
     * @return iterable|Closure[]|
     */
    protected function getCommands(): iterable
    {
        yield from [];
    }

    /**
     * The result of the callable will be printed to cli automatically, if the function has no Cli\IO parameter
     *
     * @param string   $name
     * @param callable $callable
     * @param string[] $args
     * @param string[] $desc
     *
     * @return Command
     */
    public function command(string $name, $callable, array $args = [], array $desc = []): Command
    {
        $command = new Command($name);
        $refFn = $this->invoker->reflect($callable);

        $isOutput = Php::some($refFn->getParameters(), function (ReflectionParameter $parameter): bool {
            return ($class = $parameter->getClass()) && (
                $class->isSubclassOf(OutputInterface::class) || $class->name === OutputInterface::class
            );
        });

        if (class_exists(DocBlockFactory::class) && $comment = $refFn->getDocComment()) {
            $doc = DocBlockFactory::createInstance()->create($comment);
            $command->setDescription($doc->getSummary());
            $desc = Php::arr(Php::gen($doc->getTagsByName('param'), function (Param $tag) {
                if ($paramDesc = (string)$tag->getDescription()) {
                    yield [$tag->getVariableName()] => $paramDesc;
                }
            }), $desc);
        }

        $command->setDefinition(Php::arr(
            static::params($refFn),
            function (Parameter $param) use ($args, $desc) {
                $asArg = $param->isVariadic() || Php::hasValue($param->getName(), $args);
                yield $param->input($asArg, $desc[$param->getName()] ?? null);
            })
        );

        $command->setCode(function () use ($callable, $isOutput) {
            $result = $this->invoker->call($callable, $this->provided($callable));
            if ($isOutput || !is_iterable($result)) {
                return $result;
            }
            Php::arr($this->container->get(IO::class)->render($result));
            return 0;
        });

        $this->add($command);
        return $command;
    }

    /**
     * @param ReflectionFunctionAbstract $refFn
     *
     * @return Parameter[]
     */
    protected static function params(ReflectionFunctionAbstract $refFn): array
    {
        return Php::arr($refFn->getParameters(), static function (ReflectionParameter $ref) {
            if ($ref->getClass() || $ref->isCallable()) {
                return;
            }
            $param = new Parameter($ref);
            yield [$param->getName('-')] => $param;
        });
    }

    /**
     * @param callable $callable
     *
     * @return array
     */
    private function provided($callable): array
    {
        $params = static::params($this->invoker->reflect($callable));
        $io = $this->container->get(IO::class);
        return Php::arr(
            $io->getOptions(true),
            $io->getArguments(true),
            static function ($value, $key) use ($params) {
                if (isset($params[$key])) {
                    ($params[$key]->isVariadic() && !$value) || yield [$params[$key]->getName()] => $value;
                }
            }
        );
    }

    /**
     * @param Package|string|array|callable ...$args
     * @return DI\Container|CompiledContainer
     */
    public static function di(...$args): ContainerInterface
    {
        $package = $args[0] ?? null;
        is_string($package) && $package = Package::get($package);
        if ($package instanceof Package) {
            unset($args[0]);
        } else {
            $package = Package::get('');
        }
        $config = [];
        foreach ($args as $arg) {
            $config[] = is_string($arg) ? $package->file($arg) : $arg;
        }

        return Php::di([
            Package::class => $package,
            'cli.name' => $package->name,
            'cli.version' => $package->version(),
        ], ...$config);
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $this->container->set(InputInterface::class, $input = $input ?: new ArgvInput);
        $this->container->set(OutputInterface::class, $output = $output ?: new ConsoleOutput);
        $this->container->set(IO::class, $io = new IO($input, $output));

        return parent::run($input, $output);
    }

    protected function getDefaultCommands(): array
    {
        return Php::arr(parent::getDefaultCommands(), function (Command $command) {
            yield $command->setHidden(true);
        });
    }
}
