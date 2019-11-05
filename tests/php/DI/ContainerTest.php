<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use Php\test\assert;
use DI;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Container
 */
class ContainerTest extends TestCase
{
    /**
     * @covers \Php\DI\Container::addDefinition
     * @covers \Php\DI\Container::getDefinition
     * @covers \Php\DI\Container::getDefinitions
     */
    public function testDefinitionSource(): void
    {
        $container = new Container;
        assert\type(DI\Definition\Source\MutableDefinitionSource::class, $container);
        assert\same([], $container->getDefinitions());
        assert\false($container->has('foo'));

        $container->addDefinition($def = self::def('foo', 'bar'));

        assert\true($container->has('foo'));
        assert\same($def, $container->getDefinition('foo'));
        assert\same(['foo' => $def], $container->getDefinitions());
        assert\same('bar', $container->get('foo'));
    }

    public function testNested(): void
    {
        $c1 = new Container;
        $c2 = new Container;
        $c3 = new Container(new DI\Definition\Source\SourceChain([$c1, $c2]));

        $c1->addDefinition(self::def('c1', 'C1'));
        $c2->addDefinition(self::def('c2', 'C2'));

        assert\true($c1->has('c1'));
        assert\same('C1', $c1->get('c1'));
        assert\false($c2->has('c1'));
        assert\true($c3->has('c1'));
        assert\same('C1', $c3->get('c1'));

        assert\false($c1->has('c2'));
        assert\true($c2->has('c2'));
        assert\same('C2', $c2->get('c2'));
        assert\true($c3->has('c2'));
        assert\same('C2', $c3->get('c2'));
    }

    /**
     * @param string $name
     * @param $value
     * @return DI\Definition\Definition
     */
    private static function def(string $name, $value): DI\Definition\Definition
    {
        $def = DI\value($value);
        $def->setName($name);
        return $def;
    }
}
