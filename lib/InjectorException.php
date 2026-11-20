<?php

namespace Auryn;

class InjectorException extends \Exception
{
    /**
     * The standard 'invalid invokable' message with a human readable version of the invalid callable.
     */
    protected static function invalidCallableMessage(mixed $callableOrMethodStr): string
    {
        $callableString = null;

        if (is_string($callableOrMethodStr)) {
            $callableString = $callableOrMethodStr;
        } elseif (is_array($callableOrMethodStr) &&
            array_key_exists(0, $callableOrMethodStr) &&
            array_key_exists(1, $callableOrMethodStr)) {
            if (is_string($callableOrMethodStr[0]) && is_string($callableOrMethodStr[1])) {
                $callableString = $callableOrMethodStr[0].'::'.$callableOrMethodStr[1];
            } elseif (is_object($callableOrMethodStr[0]) && is_string($callableOrMethodStr[1])) {
                $callableString = sprintf(
                    "[object(%s), '%s']",
                    $callableOrMethodStr[0]::class,
                    $callableOrMethodStr[1]
                );
            }
        }

        if (!$callableString) {
            return Injector::M_INVOKABLE;
        }

        // Prevent accidental usage of long strings from filling logs.
        return sprintf(
            "%s. Invalid callable was '%s'",
            Injector::M_INVOKABLE,
            substr($callableString, 0, 250)
        );
    }
}
