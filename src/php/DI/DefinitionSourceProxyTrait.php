<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use DI\Definition\Definition;
use DI\Definition\Source\DefinitionSource;

/**
 * @see DefinitionSource
 */
trait DefinitionSourceProxyTrait
{
    /**
     * @var DefinitionSource
     */
    private $definitionSource;

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @see DefinitionSource::getDefinition
     * @param string $name
     * @return Definition|null
     */
    public function getDefinition(string $name): ?Definition
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->definitionSource->getDefinition($name);
    }

    /**
     * @see DefinitionSource::getDefinitions
     * @return Definition[]
     */
    public function getDefinitions(): array
    {
        return $this->definitionSource->getDefinitions();
    }
}
