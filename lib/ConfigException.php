<?php

namespace Auryn;

class ConfigException extends InjectorException
{
    /**
     * Add a human readable version of the invalid callable to the standard 'invalid invokable' message.
     */
    public static function fromInvalidCallable(mixed $callableOrMethodStr, ?\Throwable $previous = null): self
    {
        return new self(self::invalidCallableMessage($callableOrMethodStr), Injector::E_INVOKABLE, $previous);
    }
}
