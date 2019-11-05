<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace php\DI;

use php;
use php\PropertiesTrait;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\Type;

/**
 * @property-read Param $tag = null
 * @property-read string $description
 * @property-read Type[]|php\Map $types
 */
class ReflectionParameter extends \ReflectionParameter
{
    use ReflectionParameterTrait;
    use PropertiesTrait\ReadOnly;

    /**
     * @var \ReflectionParameter
     */
    protected $proxy;

    /** @noinspection MagicMethodsValidityInspection */
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(\ReflectionParameter $proxy, Param $tag = null)
    {
        $this->proxy = $proxy;
        $this->properties = ['tag' => $tag];
    }

    /**
     * @see $description
     * @return string
     */
    protected function resolveDescription(): string
    {
        return $this->tag ? $this->tag->getDescription() : '';
    }

    /**
     * @see $types
     * @return php\Map
     */
    protected function resolveTypes(): php\Map
    {
        $type = $this->tag ? $this->tag->getType() : [];
        return php\map(is_iterable($type) ? $type : [$type]);
    }
}
