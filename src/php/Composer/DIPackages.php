<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\Composer;

use php;
use Composer\Package\RootPackageInterface;
use Composer\Composer;
use Composer\Package\CompletePackageInterface;
use Symfony\Component\Filesystem\Filesystem as FS;
use Composer\Util\Filesystem;

/**
 * generate namespace constants for installed packages on autoload dump
 */
class DIPackages
{
    public const RESERVED = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor'
    ];

    public $vendorDir;
    private $composer;

    /**
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer  = $composer;
        $this->vendorDir = (new Filesystem)->normalizePath(
            realpath(realpath($composer->getConfig()->get('vendor-dir')))
        ) . '/';
    }

    /**
     * @inheritdoc
     */
    public function getVendors(): iterable
    {
        $packages   = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $packages[] = $this->composer->getPackage();
        $vendors    = [];
        $im         = $this->composer->getInstallationManager();
        $fs         = new FS;
        foreach ($packages as $package) {
            if (strpos($name = $package->getName(), '/') === false) {
                continue;
            }
            $path = $package instanceof RootPackageInterface ? null : $im->getInstallPath($package);
            $dir  = $path ? $fs->makePathRelative($path, $this->vendorDir) : null;
            [$vendor, $library] = explode('/', $name);
            $vendors[$vendor][$library] = [
                'name'        => $name,
                'version'     => $package->getVersion(),
                'description' => $package instanceof CompletePackageInterface ? $package->getDescription() : null,
                'homepage'    => $package instanceof CompletePackageInterface ? $package->getHomepage() : null,
                'authors'     => $package instanceof CompletePackageInterface ? $package->getAuthors() : [],
                'dir'         => $dir,
                'root'        => $dir === null,
                'extra'       => $package->getExtra(),
            ];
        }
        return $vendors;
    }

    private function up($string): string
    {
        $string .= in_array(strtolower($string), self::RESERVED, true) ?  '_' : '';
        return str_replace('-', '_', strtoupper($string));
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $ns    = [];
        $const = [
            '',
            'namespace php {',
            '    const PACKAGES = [',
        ];

        foreach ($this->getVendors() as $vendor => $packages) {
            $ns[] = "namespace php\\VENDOR\\{$this->up($vendor)} {";
            foreach ($packages as $name => $package) {
                $ns[] = "    const {$this->up($name)} = '{$package['name']}';";

                $dir = $package['dir'] ? "VENDOR_DIR . '{$package['dir']}'" : 'BASE_DIR';

                $const[] = "        VENDOR\\{$this->up($vendor)}\\{$this->up($name)} => [";
                $const[] = "            'name'        => '{$package['name']}',";
                $const[] = "            'version'     => '{$package['version']}',";
                $const[] = "            'description' => " . var_export($package['description'], true) . ',';
                $const[] = "            'homepage'    => " . var_export($package['homepage'], true) . ',';
                $const[] = "            'dir'         => $dir,";
                $const[] = "            'authors'     => " . new php\ArrayExport((array)$package['authors']) . ',';
                $const[] = "            'extra'       => " . new php\ArrayExport($package['extra']) . ',';
                $const[] = '        ],';
                $const[] = '';
            }
            $ns[] = '}';
            $ns[] = '';
        }
        $const[] = '    ];';
        $const[] = '}';

        return implode(PHP_EOL, $ns) . implode(PHP_EOL, $const);
    }
}
