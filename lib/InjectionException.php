<?php

namespace Auryn;

class InjectionException extends InjectorException
{
    public $dependencyChain;

    public function __construct(array $inProgressMakes, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        $this->dependencyChain = array_flip($inProgressMakes);
        ksort($this->dependencyChain);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Add a human readable version of the invalid callable to the standard 'invalid invokable' message.
     */
    public static function fromInvalidCallable(
        array $inProgressMakes,
        mixed $callableOrMethodStr,
        ?\Throwable $previous = null
    ): self {
        return new self(
            $inProgressMakes,
            self::invalidCallableMessage($callableOrMethodStr),
            Injector::E_INVOKABLE,
            $previous
        );
    }

    /**
     * Returns the hierarchy of dependencies that were being created when
     * the exception occurred.
     * @return array
     */
    public function getDependencyChain(): array
    {
        return $this->dependencyChain;
    }
}
