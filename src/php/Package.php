<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php;

/**
 * @property-read string $name
 * @property-read string $version
 * @property-read string $dir
 * @property-read string $homepage
 * @property-read string $description
 * @property-read bool $root
 * @property-read array $authors
 * @property-read array $extra
 */
class Package
{
    use PropertiesTrait\ReadOnly;
    use PropertiesTrait\Init;

    private static $null;
    private static $data;
    private const CONSTANT = 'php\\PACKAGES';

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

    /**
     * @see $name
     * @return string
     */
    protected function resolveName(): ?string
    {
        return $this->properties['name'] ?? null;
    }

    /**
     * @see $version
     * @return string
     */
    protected function resolveVersion(): ?string
    {
        return $this->properties['version'] ?? null;
    }

    /**
     * @see $homepage
     * @return string
     */
    protected function resolveHomepage(): ?string
    {
        return $this->properties['homepage'] ?? null;
    }

    /**
     * @see $description
     * @return string
     */
    protected function resolveDescription(): ?string
    {
        return $this->properties['description'] ?? null;
    }

    /**
     * @see $dir
     * @return string
     */
    protected function resolveDir(): ?string
    {
        return $this->properties['dir'] ?? null;
    }

    /**
     * @see $authors
     * @return array
     */
    protected function resolveAuthors(): array
    {
        return (array)($this->properties['authors'] ?? []);
    }

    /**
     * @see $extra
     * @return array
     */
    protected function resolveExtra(): array
    {
        return (array)($this->properties['extra'] ?? []);
    }

    /**
     * @see $root
     * @return bool
     */
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
        return traverse($files, [$this, 'file']);
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
        $version = explode('.', $version);
        if (!is_int($format) && ($version[3] ?? null) === '0') {
            $format = 3;
        }
        return implode('.', array_slice($version, 0, $format));
    }
}
