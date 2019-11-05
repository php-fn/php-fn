<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\Composer;

use php\DI;
use php\test\assert;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass DIRenderer
 */
class DIRendererTest extends TestCase
{
    /**
     * @return array
     */
    public function providerClass(): array
    {
        return [
            'long namespace' => ['cl', 'ns1\\ns2', 'ns1\\ns2\\cl'],
            'short namespace' => ['cl', 'ns1', 'ns1\\cl'],
            'no namespace' => ['cl', '', 'cl'],
            'empty' => ['', '', ''],
        ];
    }

    /**
     * @covers \php\Composer\DIRenderer::getNameSpace
     * @covers \php\Composer\DIRenderer::getClassName
     *
     * @dataProvider providerClass
     *
     * @param string $expectedClassName
     * @param string $expectedNameSpace
     * @param string $class
     */
    public function testClass(string $expectedClassName, string $expectedNameSpace, string $class): void
    {
        $renderer = new DIRenderer($class);
        assert\same($expectedClassName, $renderer->getClassName());
        assert\same($expectedNameSpace, $renderer->getNameSpace());
    }

    /**
     * @return array
     */
    public function providerToString(): array
    {
        return [
            'nested' => [
                <<<EOF
namespace ns1\\ns2 {
    /**
     */
    class c1 extends \\php\\DI\\Container
    {
        /**
         * @inheritdoc
         */
        public function __construct()
        {
            \$cc = \\php\\DI::config(
                ['wiring' => 'reflection', 'cache' => false, 'proxy' => 'proxy.php', 'compile' => '/tmp/'], 
                \$sources = [
                    \\ns1\\ns2\\ns3\\c2::class => new \\ns1\\ns2\\ns3\\c2,
                    \\ns1\\ns2\\c3::class => new \\ns1\\ns2\\c3,
                    \\ns1\\c3::class => new \\ns1\\c3,
                    \\c4::class => new \\c4,
                ],
                ...\array_values(\$sources),
                ...[
                    \\php\\BASE_DIR . 'config/c1.php',
                    \\php\\BASE_DIR . 'config/c2.php',
                ],
                ...[['k1' => 'v1', 'k2' => ['v2', 'v3'], 'k3' => ['k4' => ['v5']]]]
            );

            parent::__construct(\$cc->getDefinitionSource(), \$cc->getProxyFactory(), \$cc->getWrapperContainer());        
        }
    }
}
EOF
, new DIRenderer('ns1\\ns2\\c1', [
        DI::WIRING => DI\WIRING::REFLECTION,
        'cache'   => false,
        'proxy'   => 'proxy.php',
        'compile' => '/tmp/',
    ], [
        'ns1\\ns2\\ns3\\c2',
        'ns1\\ns2\\c3',
        'ns1\\c3',
        'c4',
    ], [
        'config/c1.php',
        'config/c2.php',
    ], [
        'k1' => 'v1',
        'k2' => ['v2', 'v3'],
        'k3' => ['k4' => ['v5']],
    ]),
],

            'empty' => [
                <<<EOF
namespace  {
    /**
     */
    class c1 extends \\php\\DI\\Container
    {
        /**
         * @inheritdoc
         */
        public function __construct()
        {
            \$cc = \\php\\DI::config(
                [], 
                \$sources = [
                ],
                ...\\array_values(\$sources),
                ...[
                ],
                ...[[]]
            );

            parent::__construct(\$cc->getDefinitionSource(), \$cc->getProxyFactory(), \$cc->getWrapperContainer());        
        }
    }
}
EOF
, new DIRenderer('c1')]
        ];
    }

    /**
     * @covers \php\Composer\DIRenderer::__toString
     *
     * @dataProvider providerToString
     *
     * @param string $expected
     * @param DIRenderer $renderer
     */
    public function testToString(string $expected, DIRenderer $renderer): void
    {
        assert\same($expected, (string)$renderer);
    }
}
