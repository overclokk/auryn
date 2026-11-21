# Excluded features

Sometimes, you just have to say 'no'.

None of the features below would be ridiculous to include in this library, but for various reasons they did not
feel right. Each section describes the feature, explains why it was not included and suggests alternative solutions.

Adapted from the [rdlowrey/auryn](https://github.com/rdlowrey/auryn) `dev` branch.

* [Resolving dependencies based on the constructor chain](#resolving-dependencies-based-on-the-constructor-chain)
* [Parameter definitions not inherited from parent classes](#parameter-definitions-not-inherited-from-parent-classes)
* [Resolve interfaces and abstracts to shared instances](#resolve-interfaces-and-abstracts-to-shared-instances)
* [Optional, aka null default, parameters](#optional-aka-null-default-parameters)

## Resolving dependencies based on the constructor chain

Original discussion: https://github.com/rdlowrey/auryn/issues/35

### Description

Imagine two classes that each depend on a `UtilityClass`:

```php
interface Logger
{
    public function log(string $level, string $message): void;
}

class UtilityClass
{
    public function __construct(private Logger $logger)
    {
    }

    public function foo(): void
    {
        $this->logger->log('info', 'About to foo.');
    }
}

class ClassThatIsWorkingCorrectly
{
    public function __construct(UtilityClass $utilityClass)
    {
    }
}

class ClassRelatedToReportedBugs
{
    public function __construct(UtilityClass $utilityClass)
    {
    }
}
```

One class works correctly and you are not interested in its logs, the other one is misbehaving. It would be handy to
configure the injector so that:

* the `Logger` injected into most classes only reports at the 'error' level;
* any `Logger` created for `ClassRelatedToReportedBugs`, or any of its dependencies, reports at the 'info' level.

### Alternative solutions

#### Get a more powerful logger or logging system

For example the `FingersCrossedHandler` of [Monolog](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html)
only writes the log messages after a triggering event. Most hosted logging systems can also be configured in real time,
without touching the configuration of each server.

#### Use more contextual logging

Plain text logs are hard to filter and understand. Structured data that other tools can process makes it much easier
to understand what is happening in your application.

### Why it was not included

It is too much complexity for an injector. The complexity has to live somewhere, but it belongs to the logger and its
infrastructure, not to the injector.

## Parameter definitions not inherited from parent classes

Original discussion: https://github.com/rdlowrey/auryn/issues/133

### Description

Some people expect a class to 'inherit' the definitions of its parent class:

```php
class Foo
{
    public function __construct($key)
    {
    }
}

class Bar extends Foo
{
}

$injector = new Auryn\Injector();
$injector->define(Foo::class, [':key' => 'secret']);

$foo = $injector->make(Foo::class);
$bar = $injector->make(Bar::class);

// Auryn\InjectionException: No definition available to provision typeless
// parameter $key at position 0 in Bar::__construct() declared in Foo::
```

### Why it was not included

All configuration must be explicit, which makes it easier to reason about. With inherited definitions you would need
to know that a class has a parent, and that the parent has a definition, to understand what the object receives.

### Alternative solutions

#### Explicit definition

Define the parameters for each class that needs them:

```php
$injector->define(Foo::class, [':key' => 'secret']);
$injector->define(Bar::class, [':key' => 'secret']);
```

#### Wrap the value in a type and share it

Types can be shared, so the value is defined only once:

```php
final class ApiKey
{
    public function __construct(private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}

class Foo
{
    public function __construct(ApiKey $apiKey)
    {
    }
}

class Bar extends Foo
{
}

$injector = new Auryn\Injector();
$injector->share(new ApiKey('secret'));

$foo = $injector->make(Foo::class);
$bar = $injector->make(Bar::class);
```

## Resolve interfaces and abstracts to shared instances

Original discussion: https://github.com/rdlowrey/auryn/issues/145

### Description

Some people expect the injector to use a shared instance for the interfaces it implements:

```php
interface A
{
}

class B implements A
{
}

class C
{
    public function __construct(A $a)
    {
    }
}

$injector->share(new B());
$injector->make(C::class);

// Auryn\InjectionException: Injection definition required for interface A
```

### Alternative solution

Define an alias from each interface to its concrete class, the shared instance is then used:

```php
$injector->share(new B());
$injector->alias(A::class, B::class);
```

### Why it was not included

All configuration must be explicit. To see which class is created for an interface you would otherwise have to search
the code or run it, instead of inspecting the injector configuration.

When a second class implementing the interface is added, the injector would also need to either pick one or fail
with "multiple available types". Both choices are more surprising and slower to fix than an explicit alias.

## Optional, aka null default, parameters

Original discussion: https://github.com/rdlowrey/auryn/issues/190

### Description

When a parameter has a default value, Auryn uses that default unless the injector has been told how to create its
type:

```php
class OptionalDependency
{
}

class DependsOnOptionalDependency
{
    public function __construct(public ?OptionalDependency $dependency = null)
    {
    }
}

$injector->make(DependsOnOptionalDependency::class)->dependency; // null
```

Some people might expect `OptionalDependency` to be created.

### Why the default is used

* Auryn defaults to explicit configuration, so an optional dependency is not created unless it is configured.
* Some classes cannot be instantiated, for example abstract classes or classes with dependencies that cannot be
  created. Creating optional types by default would need extra "do not create this class" rules.

### Alternative solution: be explicit

Configure the type with an alias, a delegate or a share, and it is injected instead of the default:

```php
$injector->alias(OptionalDependency::class, OptionalDependency::class);
// or
$injector->share(OptionalDependency::class);
// or
$injector->delegate(OptionalDependency::class, static fn() => new OptionalDependency());
```

### Alternative solution: new in initializers

Since PHP 8.1 the default value can create the object:

```php
class DependsOnOptionalDependency
{
    public function __construct(public OptionalDependency $dependency = new OptionalDependency())
    {
    }
}
```
