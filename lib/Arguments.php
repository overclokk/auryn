<?php

declare(strict_types=1);

namespace Auryn;

/**
 * Immutable builder for definitions and the $args of make() and execute(),
 * it writes the Injector::A_* prefixes so they cannot be forgotten or mistyped.
 */
final class Arguments
{
    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __construct(private readonly array $arguments = [])
    {
    }

    /**
     * Pass the value as is to the parameter at this position.
     */
    public function position(int $index, mixed $value): self
    {
        return $this->with($index, $value);
    }

    /**
     * Make the class, or the list of classes for a variadic parameter, with the injector.
     *
     * @param class-string|list<class-string> $class
     */
    public function make(string $parameter, string|array $class): self
    {
        return $this->with($parameter, $class);
    }

    /**
     * Pass the value as is to the parameter with this name.
     */
    public function raw(string $parameter, mixed $value): self
    {
        return $this->with(Injector::A_RAW . $parameter, $value);
    }

    /**
     * Pass the value returned by the delegate.
     */
    public function delegate(string $parameter, mixed $delegate): self
    {
        return $this->with(Injector::A_DELEGATE . $parameter, $delegate);
    }

    /**
     * Make the class with its own arguments.
     *
     * @param class-string $class
     * @param self|array<array-key, mixed> $arguments
     */
    public function define(string $parameter, string $class, self|array $arguments = []): self
    {
        return $this->with(
            Injector::A_DEFINE . $parameter,
            [$class, $arguments instanceof self ? $arguments->toArray() : $arguments]
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->arguments;
    }

    private function with(int|string $key, mixed $value): self
    {
        $arguments = $this->arguments;
        $arguments[$key] = $value;

        return new self($arguments);
    }
}
