<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

interface Repository
{
    public function name(): string;
}

final class MysqlRepository implements Repository
{
    public function name(): string
    {
        return 'mysql';
    }
}

final class CacheRepository implements Repository
{
    public function name(): string
    {
        return 'cache';
    }
}

final class Finder
{
    /** @var list<Repository> */
    private array $repositories;

    public function __construct(Repository ...$repositories)
    {
        $this->repositories = $repositories;
    }

    public function names(): string
    {
        return implode(', ', array_map(static fn(Repository $repository) => $repository->name(), $this->repositories));
    }
}

final class Greeter
{
    /** @var list<string> */
    private array $names;

    public function __construct(string ...$names)
    {
        $this->names = $names;
    }

    public function greet(): string
    {
        return 'Hello ' . implode(' and ', $this->names);
    }
}

$injector = new Auryn\Injector();

// Class names: each one is made by the injector, shares and aliases apply.
$injector->define(Finder::class, ['repositories' => [MysqlRepository::class, CacheRepository::class]]);
print $injector->make(Finder::class)->names() . PHP_EOL;

// Raw values.
$injector->define(Greeter::class, [':names' => ['Alice', 'Bob']]);
print $injector->make(Greeter::class)->greet() . PHP_EOL;

// $args of make() replace the definition with the same key for this call only.
print $injector->make(Finder::class, ['repositories' => [CacheRepository::class]])->names() . PHP_EOL;

// A delegate returning the list.
$injector = new Auryn\Injector();
$injector->define(Finder::class, ['+repositories' => static fn() => [new CacheRepository(), new MysqlRepository()]]);
print $injector->make(Finder::class)->names() . PHP_EOL;
