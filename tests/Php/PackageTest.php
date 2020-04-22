<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PackageTest extends TestCase
{
    private const FOO = [
        'name' => 'foo',
        'type' => 'library',
        'version' => '1.2.3.0',
        'description' => 'foo-d',
        'homepage' => 'foo-h',
        'authors' => ['foo-a'],
        'dir' => '/foo-dir/',
        'root' => true,
        'extra' => ['foo-e'],
    ];

    public function setUp(): void
    {
        $prop = (new ReflectionClass(Package::class))->getProperty('data');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    public function testNullObject(): void
    {
        self::assertInstanceOf(Package::class, Package::get('foo'));
        self::assertSame(Package::get('foo'), $package = Package::get('bar'));
        self::assertNull(Package::get('bar', true));
        self::assertNull($package->name);
        self::assertNull($package->type);
        self::assertNull($package->version);
        self::assertNull($package->homepage);
        self::assertNull($package->description);
        self::assertNull($package->dir);
        self::assertFalse($package->root);
        self::assertSame([], $package->extra);
        self::assertSame([], $package->authors);
        self::assertSame('foo/bar', $package->file('foo/bar'));
        self::assertSame('/foo/bar', $package->file('/foo/bar'));
        self::assertSame(['foo/bar', '/foo/bar'], $package->files('foo/bar', '/foo/bar'));
        self::assertSame('', $package->version());
        self::assertSame('', $package->version(1));
        self::assertSame('', $package->version(2));
        self::assertSame('', $package->version(3));
        self::assertNull($package->version(true));
    }

    public function testDefined(): void
    {
        $package = new Package(self::FOO);
        self::assertSame('foo', $package->name);
        self::assertSame('library', $package->type);
        self::assertSame('1.2.3.0', $package->version);
        self::assertSame('foo-h', $package->homepage);
        self::assertSame('foo-d', $package->description);
        self::assertSame('/foo-dir/', $package->dir);
        self::assertTrue($package->root);
        self::assertSame(['foo-e'], $package->extra);
        self::assertSame(['foo-a'], $package->authors);
        self::assertSame('/foo-dir/foo/bar', $package->file('foo/bar'));
        self::assertSame('/foo/bar', $package->file('/foo/bar'));
        self::assertSame(['/foo-dir/foo/bar', '/foo/bar'], $package->files('foo/bar', '/foo/bar'));
        self::assertSame('1.2.3', $package->version());
        self::assertSame('1', $package->version(1));
        self::assertSame('1.2', $package->version(2));
        self::assertSame('1.2.3', $package->version(3));
        self::assertSame('1.2.3.0', $package->version(true));
    }

    public function testVersion(): void
    {
        self::assertSame('1.2.3.4', (new Package(['version' => '1.2.3.4']))->version());
        self::assertSame('1.2.3', (new Package(['version' => '1.2.3.0']))->version());
    }
}
