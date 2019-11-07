<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

/** @noinspection PhpDocMissingThrowsInspection */

namespace Php\DI;

use Php;

use Invoker\{InvokerInterface, ParameterResolver, ParameterResolver\GeneratorResolver, Reflection\CallableReflection};
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;

/**
 */
class Invoker extends ParameterResolver\ResolverChain implements InvokerInterface
{
    /**
     * @var \Invoker\Invoker
     */
    private $invoker;

    /**
     * @param mixed ...$resolvers
     */
    public function __construct(...$resolvers)
    {
        $this->invoker = new \Invoker\Invoker($this);

        parent::__construct(Php::traverse($resolvers, function ($candidate): ParameterResolver\ParameterResolver {
            if ($candidate instanceof ParameterResolver\ParameterResolver) {
                return $candidate;
            }
            if ($candidate instanceof ContainerInterface) {
                $this->invoker = new \Invoker\Invoker($this, $candidate);
                return new ParameterResolver\Container\TypeHintContainerResolver($candidate);
            }
            return new GeneratorResolver(static function ($parameter, array $provided = [], $tag = null) use (
                $candidate
            ) {
                return $candidate(new ReflectionParameter($parameter, $tag), $provided, $tag);
            });
        }));
    }

    /**
     * @param callable $candidate
     *
     * @return callable
     */
    public function resolve($candidate): callable
    {
        if ($resolver = $this->invoker->getCallableResolver()) {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $resolver->resolve($candidate);
        }
        Php::isCallable($candidate) || Php::fail('argument $candidate is not callable');
        return $candidate;
    }

    /**
     * @param callable $candidate
     *
     * @return ReflectionFunctionAbstract
     */
    public function reflect($candidate): ReflectionFunctionAbstract
    {
        return CallableReflection::create($this->resolve($candidate));
    }

    /**
     * @param callable $candidate
     * @param array $provided
     *
     * @return array
     */
    public function parameters($candidate, array $provided = []): array
    {
        return $this->getParameters($this->reflect($candidate), $provided, []);
    }

    /**
     * @inheritdoc
     */
    public function call($callable, array $parameters = [])
    {
        return $this->invoker->call($callable, $parameters);
    }
}
