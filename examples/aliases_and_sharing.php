<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

interface Clock
{
    public function now(): string;
}

final class FixedClock implements Clock
{
    public function now(): string
    {
        return '2026-01-01';
    }
}

final class Logger
{
}

final class Service
{
    public function __construct(public Clock $clock, public Logger $logger)
    {
    }
}

$injector = new Auryn\Injector();

// An interface needs an alias to a concrete class.
$injector->alias(Clock::class, FixedClock::class);

// Sharing a class name creates a single instance on the first make().
$injector->share(Logger::class);

$first = $injector->make(Service::class);
$second = $injector->make(Service::class);

print 'Clock: ' . $first->clock->now() . PHP_EOL;
print 'Same logger: ' . var_export($first->logger === $second->logger, true) . PHP_EOL;

// Sharing an instance uses that object, a second different instance of the same type is rejected.
$injector = new Auryn\Injector();
$logger = new Logger();
$injector->share($logger);
print 'Shared instance: ' . var_export($injector->make(Logger::class) === $logger, true) . PHP_EOL;

try {
    $injector->share(new Logger());
} catch (Auryn\ConfigException $exception) {
    print 'Double share rejected: ' . ($exception->getCode() === Auryn\Injector::E_DOUBLE_SHARE ? 'yes' : 'no') . PHP_EOL;
}
