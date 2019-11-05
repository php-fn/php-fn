<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use ArrayIterator;
use IteratorAggregate;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 */
class DIProvider implements IteratorAggregate
{
    /**
     * @var DIRenderer[]|ArrayIterator
     */
    private $di;

    /**
     * @var array
     */
    private $defaultConfig = [];

    /**
     * @var array[]
     */
    private $containerConfig = [];

    /**
     * @param array $di
     * @param array $diConfig
     */
    public function __construct(array $di, array $diConfig = [])
    {
        foreach ($diConfig as $key => $value) {
            if (static::getClass($key)) {
                $this->containerConfig[$key] = (array)$value;
            } else {
                $this->defaultConfig[$key] = $value;
            }
        }
        $this->di = new ArrayIterator;
        foreach ($this->init($di) as $class => $config) {
            $this->di[$class = self::getClass($class)] = new DIRenderer(
                $class,
                $config['config'],
                array_map(static function ($container) {
                    return self::getClass($container);
                }, $config['containers']),
                $config['files'],
                $config['values']
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): ArrayIterator
    {
        return $this->di;
    }

    /**
     * @param array $di
     * @return array
     */
    private function init(array $di): array
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($di),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $class = '@Php\\Composer\\DI';
        $configs = [$class => [
            'config' => $this->config($class),
            'files' => [],
            'containers' => [],
            'values' => [],
            'root' => true,
        ]];
        $parents = [-1 => $class];

        foreach ($it as $key => $value) {
            $depth = $it->getDepth();
            $parents[$depth] = $key;
            $parents = array_slice($parents, 0, $depth + 2, true);
            $parent = $parents[$depth - 1];

            if (static::getClass($key)) {

                $configs[$key] = $configs[$key] ?? [
                    'config' => $this->config($key),
                    'files' => is_string($value) ? [$value] : [],
                    'containers' => [],
                    'values' => [],
                    'root' => false,
                ];

                if (isset($configs[$parent])) {
                    $configs[$parent]['containers'][] = $key;
                }

            } else if (isset($configs[$parent])) {
                if (is_numeric($key)) {
                    $configs[$parent][self::getClass($value) ? 'containers' : 'files'][] = $value;
                } else {
                    $configs[$parent]['values'][$key] = $value;
                }
            }
        }

        return $configs;
    }

    /**
     * @param string $container
     *
     * @return array
     */
    private function config(string $container): array
    {
        return ($this->containerConfig[$container] ?? []) + $this->defaultConfig;
    }

    /**
     * @param string $candidate
     * @return string
     */
    private static function getClass(string $candidate): string
    {
        return (string)(strpos($candidate, '@') === 0 ? substr($candidate, 1) : '');
    }
}
