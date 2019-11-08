<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use Php\test\assert;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PackageTest extends TestCase
{
    private const FOO = [
        'name' => 'foo',
        'version' => '1.2.3.0',
        'description' => 'foo-d',
        'homepage' => 'foo-h',
        'authors' => ['foo-a'],
        'dir' => '/foo-dir/',
        'root' => true,
        'extra' => ['foo-e'],
    ];

    public function setUp()
    {
        $prop = (new ReflectionClass(Package::class))->getProperty('data');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    public function testNullObject(): void
    {
        assert\type(Package::class, Package::get('foo'));
        assert\same(Package::get('foo'), $package = Package::get('bar'));
        assert\same(null, Package::get('bar', true));
        assert\same(null, $package->name);
        assert\same(null, $package->version);
        assert\same(null, $package->homepage);
        assert\same(null, $package->description);
        assert\same(null, $package->dir);
        assert\false($package->root);
        assert\same([], $package->extra);
        assert\same([], $package->authors);
        assert\same('foo/bar', $package->file('foo/bar'));
        assert\same('/foo/bar', $package->file('/foo/bar'));
        assert\same(['foo/bar', '/foo/bar'], $package->files('foo/bar', '/foo/bar'));
        assert\same('', $package->version());
        assert\same('', $package->version(1));
        assert\same('', $package->version(2));
        assert\same('', $package->version(3));
        assert\same(null, $package->version(true));
    }

    public function testDefined(): void
    {
        $package = new Package(self::FOO);
        assert\same('foo', $package->name);
        assert\same('1.2.3.0', $package->version);
        assert\same('foo-h', $package->homepage);
        assert\same('foo-d', $package->description);
        assert\same('/foo-dir/', $package->dir);
        assert\true($package->root);
        assert\same(['foo-e'], $package->extra);
        assert\same(['foo-a'], $package->authors);
        assert\same('/foo-dir/foo/bar', $package->file('foo/bar'));
        assert\same('/foo/bar', $package->file('/foo/bar'));
        assert\same(['/foo-dir/foo/bar', '/foo/bar'], $package->files('foo/bar', '/foo/bar'));
        assert\same('1.2.3', $package->version());
        assert\same('1', $package->version(1));
        assert\same('1.2', $package->version(2));
        assert\same('1.2.3', $package->version(3));
        assert\same('1.2.3.0', $package->version(true));
    }

    public function testVersion(): void
    {
        assert\same('1.2.3.4', (new Package(['version' => '1.2.3.4']))->version());
        assert\same('1.2.3', (new Package(['version' => '1.2.3.0']))->version());
    }
}
