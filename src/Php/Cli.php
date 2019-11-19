<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace Php;

use Invoker\ParameterResolver;
use Php\Cli\IO;
use Php\Cli\Parameter;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use Symfony\Component\Console\{Application,
    Command\Command,
    Input\ArgvInput,
    Input\InputInterface,
    Output\ConsoleOutput,
    Output\OutputInterface};

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
        if (!$container instanceof DI\Container) {
            $container = new DI\Container(null , null, $container);
        }
        $this->container = $container;
        $this->container->set(static::class, $this);
        $this->invoker   = new DI\Invoker(
            new ParameterResolver\AssociativeVariadicResolver,
            new ParameterResolver\AssociativeArrayResolver,
            new ParameterResolver\TypeHintResolver,
            $container,
            new ParameterResolver\Container\ParameterNameContainerResolver($container),
            new ParameterResolver\DefaultValueResolver
        );

        parent::__construct($this->value('cli.name'), $this->value('cli.version'));
        foreach ($this->value('cli.commands', []) as $name => $command) {
            if (is_numeric($name) && is_string($command)) {
                $name = end($name = explode('\\', $command));
            }
            $this->command(strtolower($name), $command);
        }
    }

    /**
     * @param Package|string|array|callable ...$args
     *
     * @return Cli
     */
    public static function fromPackage(...$args): self
    {
        $package = $args[0] ?? null;
        is_string($package) && $package = Package::get($package);
        if ($package instanceof Package) {
            unset($args[0]);
        } else {
            $package = Package::get('');
        }
        $fns    = [];
        $config = [];
        foreach ($args as $arg) {
            if (Php::isCallable($arg)) {
                $fns[] = $arg;
            } else {
                $config[] = is_string($arg) ? $package->file($arg) : $arg;
            }
        }

        $di = DI::create([
            Package::class => $package,
            'cli.name' => $package->name,
            'cli.version' => $package->version(),
        ], ...$config);

        $cli = new static($di);
        foreach ($fns as $fn) {
            $result = $di->call($fn);
            foreach (is_iterable($result) ? $result : [] as $name => $command) {
                $cli->command($name, ...array_values(is_array($command) ? $command : [$command]));
            }
        }

        return $cli;
    }

    protected function getDefaultCommands(): array
    {
        return Php::arr(parent::getDefaultCommands(), function (Command $command) {
            yield $command->setHidden(true);
        });
    }

    private function value(string $id, $default = null)
    {
        return $this->container->has($id) ? $this->container->get($id) : $default;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $this->container->set(InputInterface::class, $input = $input ?: new ArgvInput);
        $this->container->set(OutputInterface::class, $output = $output ?: new ConsoleOutput);
        $this->container->set(IO::class, $io = new IO($input, $output));

        return parent::run($input, $output);
    }

    public function __invoke(): int
    {
        return $this->run();
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
        $refFn   = $this->invoker->reflect($callable);

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
                yield $param->input(
                    $param->isVariadic() || Php::hasValue($param->getName(), $args),
                    $desc[$param->getName()] ?? null
                );
            })
        );

        $command->setCode(function() use($callable, $isOutput) {
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
     * @param callable $callable
     *
     * @return array
     */
    private function provided($callable): array
    {
        $params = static::params($this->invoker->reflect($callable));
        $io = $this->container->get(IO::class);
        $arr =  Php::arr(
            $io->getOptions(true),
            $io->getArguments(true),
            static function ($value, $key) use ($params) {
                if (isset($params[$key])) {
                    ($params[$key]->isVariadic() && !$value) || yield [$params[$key]->getName()] => $value;
                }
            }
        );
        return $arr;
    }

    /**
     * @param ReflectionFunctionAbstract $refFn
     *
     * @return Map|Parameter[]
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
}
