<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

final class Greeting
{
    public function text(): string
    {
        return 'Hello';
    }
}

final class Controller
{
    public function __construct(private Greeting $greeting)
    {
    }

    public function show(string $name): string
    {
        return $this->greeting->text() . ' ' . $name;
    }

    public static function version(): string
    {
        return '2.x';
    }
}

final class InvokableAction
{
    public function __construct(private Greeting $greeting)
    {
    }

    public function __invoke(): string
    {
        return $this->greeting->text() . ' from an invokable';
    }
}

$injector = new Auryn\Injector();

// Closures receive their dependencies, $args works as for make().
print $injector->execute(static fn(Greeting $greeting, string $name) => $greeting->text() . ' ' . $name, [':name' => 'closure']) . PHP_EOL;

// "Class::method" builds the object with its dependencies before calling the method.
print $injector->execute('Controller::show', [':name' => 'method string']) . PHP_EOL;

// [object, method] and static methods.
print $injector->execute([$injector->make(Controller::class), 'show'], [':name' => 'array callable']) . PHP_EOL;
print 'Version ' . $injector->execute([Controller::class, 'version']) . PHP_EOL;

// An invokable class name is made and called.
print $injector->execute(InvokableAction::class) . PHP_EOL;
