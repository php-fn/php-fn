<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\Cli;

use php;

use Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Merge capabilities of input and output interfaces
 */
class IO extends SymfonyStyle
{
    /**
     * @var int
     */
    public const VERBOSITY =
        self::VERBOSITY_QUIET |
        self::VERBOSITY_NORMAL |
        self::VERBOSITY_VERBOSE |
        self::VERBOSITY_VERY_VERBOSE |
        self::VERBOSITY_DEBUG;

    /**
     * @var InputInterface
     */
    private $inputInterface;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->inputInterface = $input;
        parent::__construct($input, $output);
    }

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->inputInterface;
    }

    /**
     * @see InputInterface::getArguments
     *
     * @param bool $provided
     *
     * @return array
     */
    public function getArguments(bool $provided = false): array
    {
        $arguments = $this->getInput()->getArguments();
        return $provided ? php\traverse($arguments, function($value, $key) {
            return $this->hasArgument($key) ? $value : null;
        }) : $arguments;
    }

    /**
     * @see InputInterface::getOptions
     *
     * @param bool $provided
     *
     * @return array
     */
    public function getOptions(bool $provided = false): array
    {
        $options = $this->getInput()->getOptions();
        return $provided ? php\traverse($options, function($value, $key) {
            return $this->hasOption($key) ? $value : null;
        }) : $options;
    }

    /**
     * @see InputInterface::getArgument
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getArgument(string $name)
    {
        return $this->getInput()->getArgument($name);
    }

    /**
     * @see InputInterface::getOption
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->getInput()->getOption($name);
    }

    /**
     * @see InputInterface::hasArgument
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasArgument(string $name): bool
    {
        return $this->getInput()->hasArgument($name);
    }

    /**
     * @see InputInterface::hasOption
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return $this->getInput()->hasOption($name);
    }

    /**
     * @param iterable $result
     *
     * @return Generator
     */
    public function render(iterable $result): Generator
    {
        foreach ($result as $key => $line) {
            yield $key => $line;
            $line = $line instanceof Renderable ? $line : new Renderable($line);
            $line->toCli($this);
        }
    }
}
