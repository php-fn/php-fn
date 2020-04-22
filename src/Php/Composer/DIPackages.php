<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\Composer;

use Composer\Composer;
use Composer\Package\CompletePackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Util\Filesystem;
use Php;
use Symfony\Component\Filesystem\Filesystem as FS;

/**
 * generate namespace constants for installed packages on autoload dump
 */
class DIPackages
{
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
                'type'        => $package->getType(),
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
        return str_replace('-', '_', strtoupper(Php\Lang::sanitize($string)));
    }

    public function __toString()
    {
        $ns    = [];
        $const = [
            '',
            'namespace Php {',
            '    const PACKAGES = [',
        ];

        foreach ($this->getVendors() as $vendor => $packages) {
            $ns[] = "namespace Php\\VENDOR\\{$this->up($vendor)} {";
            foreach ($packages as $name => $package) {
                $ns[] = "    const {$this->up($name)} = '{$package['name']}';";

                $dir = $package['dir'] ? "VENDOR_DIR . '{$package['dir']}'" : 'BASE_DIR';

                $const[] = "        VENDOR\\{$this->up($vendor)}\\{$this->up($name)} => [";
                $const[] = "            'name'        => '{$package['name']}',";
                $const[] = "            'type'        => '{$package['type']}',";
                $const[] = "            'version'     => '{$package['version']}',";
                $const[] = "            'description' => " . var_export($package['description'], true) . ',';
                $const[] = "            'homepage'    => " . var_export($package['homepage'], true) . ',';
                $const[] = "            'dir'         => $dir,";
                $const[] = "            'authors'     => " . new Php\ArrayExport((array)$package['authors']) . ',';
                $const[] = "            'extra'       => " . new Php\ArrayExport($package['extra']) . ',';
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
