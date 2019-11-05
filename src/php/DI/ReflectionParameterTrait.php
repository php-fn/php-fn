<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

namespace Php\DI;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionType;

/**
 */
trait ReflectionParameterTrait
{
    /**
     * @see \ReflectionParameter::export
     * @return string
     */
    public static function export($function, $parameter, $return = null)
    {
        return parent::{__FUNCTION__}(...func_get_args());
    }

    /**
     * @see \ReflectionParameter::__toString
     * @return string
     */
    public function __toString()
    {
        return (string)call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getName
     * @return string
     */
    public function getName()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getType
     * @return ReflectionType|null
     */
    public function getType()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::hasType
     * @return bool
     */
    public function hasType()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isPassedByReference
     * @return bool
     */
    public function isPassedByReference()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::canBePassedByValue
     * @return bool
     */
    public function canBePassedByValue()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getDeclaringFunction
     * @return ReflectionFunctionAbstract
     */
    public function getDeclaringFunction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getDeclaringClass
     * @return ReflectionClass
     */
    public function getDeclaringClass()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getClass
     * @return ReflectionClass
     */
    public function getClass()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isArray
     * @return bool
     */
    public function isArray()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isCallable
     * @return bool
     */
    public function isCallable()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::allowsNull
     * @return bool
     */
    public function allowsNull()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getPosition
     * @return int
     */
    public function getPosition()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isOptional
     * @return bool
     */
    public function isOptional()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isDefaultValueAvailable
     * @return bool
     */
    public function isDefaultValueAvailable()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getDefaultValue
     * @return mixed
     */
    public function getDefaultValue()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isDefaultValueConstant
     * @return bool
     */
    public function isDefaultValueConstant()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::getDefaultValueConstantName
     * @return string
     */
    public function getDefaultValueConstantName()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @see \ReflectionParameter::isVariadic
     * @return bool
     */
    public function isVariadic()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }
}
