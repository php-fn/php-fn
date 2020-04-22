<?php declare(strict_types=1);
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php;

/**
 * @property-read string|null $name
 * @property-read string|null $type
 * @property-read string|null $version
 * @property-read string|null $dir
 * @property-read string|null $homepage
 * @property-read string|null $description
 * @property-read bool $root
 * @property-read array $authors
 * @property-read array $extra
 */
class Package
{
    /**
     * @see resolveName, resolveType, resolveVersion, resolveHomepage, resolveDescription
     * @see resolveDir, resolveAuthors, resolveExtra, resolveRoot
     */
    use PropertiesTrait\ReadOnly;
    use PropertiesTrait\Init;

    private static $null;
    private static $data;
    private const CONSTANT = 'Php\\PACKAGES';

    /**
     * @param string $name
     * @param bool $assert
     *
     * @return Package|null
     */
    public static function get(string $name, bool $assert = false): ?self
    {
        self::$null ?: self::$null = new static([]);
        self::$data === null && self::$data = defined(self::CONSTANT) ? constant(self::CONSTANT) : [];

        if ($package = self::$data[$name] ?? null) {
            return new static($package);
        }
        return $assert ? null : self::$null;
    }

    protected function resolveName(): ?string
    {
        return $this->properties['name'] ?? null;
    }

    protected function resolveType(): ?string
    {
        return $this->properties['type'] ?? null;
    }

    protected function resolveVersion(): ?string
    {
        return $this->properties['version'] ?? null;
    }

    protected function resolveHomepage(): ?string
    {
        return $this->properties['homepage'] ?? null;
    }

    protected function resolveDescription(): ?string
    {
        return $this->properties['description'] ?? null;
    }

    protected function resolveDir(): ?string
    {
        return $this->properties['dir'] ?? null;
    }

    protected function resolveAuthors(): array
    {
        return (array)($this->properties['authors'] ?? []);
    }

    protected function resolveExtra(): array
    {
        return (array)($this->properties['extra'] ?? []);
    }

    protected function resolveRoot(): bool
    {
        return (bool)($this->properties['root'] ?? false);
    }

    /**
     * @param string ...$files
     * @return string[]
     */
    public function files(string ...$files): array
    {
        return Php::arr($files, function ($file) {
            yield $this->file($file);
        });
    }

    /**
     * @param string|null $file
     * @return string
     */
    public function file(string $file = null): ?string
    {
        return $file[0] === DIRECTORY_SEPARATOR ? $file : $this->dir . $file;
    }

    /**
     * @param bool|int $format null => remove patch level if empty, int => number of levels
     * @return string
     */
    public function version($format = null): ?string
    {
        $version = $this->version;
        if ($format === true) {
            return $version;
        }
        $version = explode('.', (string)$version);
        if (!is_int($format) && ($version[3] ?? null) === '0') {
            $format = 3;
        }
        return implode('.', array_slice($version, 0, $format));
    }
}
