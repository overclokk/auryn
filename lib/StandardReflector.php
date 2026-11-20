<?php

namespace Auryn;

class StandardReflector implements Reflector
{
    public function getClass($class)
    {
        return new \ReflectionClass($class);
    }

    public function getCtor($class)
    {
        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->getConstructor();
    }

    public function getCtorParams($class)
    {
        return ($reflectedCtor = $this->getCtor($class))
            ? $reflectedCtor->getParameters()
            : null;
    }

    public function getParamTypeHint(\ReflectionFunctionAbstract $function, \ReflectionParameter $param)
    {
        $type = $param->getType();
        if ($type instanceof \ReflectionNamedType) {
            // getName() has no leading "?" for nullable types, unlike the string cast.
            return $type->isBuiltin() ? null : $type->getName();
        }

        return $type ? (string) $type : null;
    }

    public function getFunction($functionName)
    {
        return new \ReflectionFunction($functionName);
    }

    public function getMethod($classNameOrInstance, $methodName)
    {
        $className = is_string($classNameOrInstance)
            ? $classNameOrInstance
            : $classNameOrInstance::class;

        return new \ReflectionMethod($className, $methodName);
    }
}
