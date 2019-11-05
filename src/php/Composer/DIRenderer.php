<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use Php\ArrayExport;

/**
 */
class DIRenderer
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var ArrayExport
     */
    private $config;

    /**
     * @var array
     */
    private $containers;

    /**
     * @var array
     */
    private $files;

    /**
     * @var ArrayExport
     */
    private $values;

    /**
     * @param string $class
     * @param array $config
     * @param array $containers
     * @param array $files
     * @param array $values
     */
    public function __construct(
        string $class,
        array $config = [],
        array $containers = [],
        array $files = [],
        array $values = []
    ) {
        $this->class = $class;
        $this->config = new ArrayExport($config);

        $this->containers = implode('', array_map(static function (string $container): string {
            return "\n                    \\{$container}::class => new \\{$container},";
        }, $containers));

        $this->files = implode('', array_map(static function (string $file): string {
            return "\n                    \\Php\\BASE_DIR . '$file',";
        }, $files));

        $this->values = new ArrayExport($values);
    }

    public function getNameSpace(): string
    {
        return substr($this->class, 0, -(strlen($this->getClassName()) + 1));
    }

    public function getClassName(): string
    {
        $parts = explode('\\', $this->class);

        return (string)end($parts);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return <<<EOF
namespace {$this->getNameSpace()} {
    /**
     */
    class {$this->getClassName()} extends \\Php\\DI\\Container
    {
        /**
         * @inheritdoc
         */
        public function __construct()
        {
            \$cc = \\Php\\DI::config(
                {$this->config}, 
                \$sources = [{$this->containers}
                ],
                ...\\array_values(\$sources),
                ...[{$this->files}
                ],
                ...[{$this->values}]
            );

            parent::__construct(\$cc->getDefinitionSource(), \$cc->getProxyFactory(), \$cc->getWrapperContainer());        
        }
    }
}
EOF;
    }
}
