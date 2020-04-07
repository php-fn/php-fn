<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use Php\ArrayExport;

class DIRenderer
{
    private $class;

    private $config;

    private $containers;

    private $files;

    private $values;

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
        return (string) substr($this->class, 0, -(strlen($this->getClassName()) + 1));
    }

    public function getClassName(): string
    {
        $parts = explode('\\', $this->class);

        return (string)end($parts);
    }

    public function __toString(): string
    {
        return <<<EOF
namespace {$this->getNameSpace()} {
    class {$this->getClassName()} extends \\Php\\DI\\Container
    {
        public function __construct()
        {
            \$cc = \\Php\\DI\\ContainerConfiguration::config(
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
