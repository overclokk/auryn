# auryn [![Build Status](https://github.com/overclokk/auryn/actions/workflows/ci.yml/badge.svg)](https://github.com/overclokk/auryn/actions)

## Thank you

This package is a fork of [rdlowrey/auryn](https://github.com/rdlowrey/auryn), and it would not exist without the
work of the people who created and maintained it for more than ten years.

A big thank you to [Daniel Lowrey](https://github.com/rdlowrey), who created auryn, to
[Dan Ackroyd](https://github.com/Danack) and [Levi Morrison](https://github.com/morrisonlevi), who maintained and
evolved it, and to every [contributor](https://github.com/rdlowrey/auryn/graphs/contributors), including
[Niklas Keller](https://github.com/kelunik) and [Markus Staab](https://github.com/staabm), for their time, their
care and their ideas.

Their clear design, an injector driven by explicit configuration instead of hidden magic, is the reason this fork
keeps going. The fixes, docs and features ported from the original repository keep their history, and every
improvement here builds on their work.

Even if this thank you is written by an AI it reflect what I really think and I couldn't have written it any better as I'm not a native English

auryn is a recursive dependency injector. Use auryn to bootstrap and wire together
S.O.L.I.D., object-oriented PHP applications.

##### How It Works

Among other things, auryn recursively instantiates class dependencies based on the parameter
type-hints specified in class constructor signatures. This requires the use of Reflection. You may
have heard that "reflection is slow". Let's clear something up: *anything* can be "slow" if you're
doing it wrong. Reflection is an order of magnitude faster than disk access and several orders of
magnitude faster than retrieving information (for example) from a remote database. Additionally,
each reflection offers the opportunity to cache the results if you're worried about speed. auryn
caches any reflections it generates to minimize the potential performance impact.

> auryn **is NOT** a Service Locator. DO NOT turn it into one by passing the injector into your
> application classes. Service Locator is an anti-pattern; it hides class dependencies, makes code
> more difficult to maintain and makes a liar of your API! You should *only* use an injector for
> wiring together the disparate parts of your application during your bootstrap phase.

## The Guide

**Basic Usage**

* [Basic Instantiation](#basic-instantiation)
* [Injection Definitions](#injection-definitions)
* [Type-Hint Aliasing](#type-hint-aliasing)
* [Non-Class Parameters](#non-class-parameters)
* [Global Parameter Definitions](#global-parameter-definitions)

**Advanced Usage**

* [Instance Sharing](#instance-sharing)
* [Instance Proxy](#instance-proxy)
* [Instantiation Delegates](#instantiation-delegates)
* [Prepares and Setter Injection](#prepares-and-setter-injection)
* [Injecting for Execution](#injecting-for-execution)
* [Custom make() and execute() arguments](#custom-make-and-execute-arguments)
* [Dependency Resolution](#dependency-resolution)

**Example Use Cases**

* [Avoiding Evil Singletons](#avoiding-evil-singletons)
* [Application Bootstrapping](#app-bootstrapping)
* [When app-bootstrapping by auryn is not possible](#when-app-bootstrapping-by-auryn-is-not-possible)

**Advanced Patterns**

* [Variadic dependencies](#variadic-dependencies)
* [Context objects and multiple instances of the same type](#context-objects-and-multiple-instances-of-the-same-type)

**[Excluded features](excluded_features.md)**: what auryn does not do on purpose, and the alternatives.


## Requirements and Installation

- auryn 2.x requires PHP 8.2 or higher.
- auryn 1.x (the [`1.x` branch](https://github.com/overclokk/auryn/tree/1.x)) supports PHP 7.1 to 8.4 and receives fixes only.
- Lazy loading proxies need `friendsofphp/proxy-manager-lts`, `ReflectionCacheApc` needs the `apcu` extension.

#### Installation

```bash
composer require overclokk/auryn
```

The package replaces `rdlowrey/auryn`, so libraries depending on it keep working.

#### Upgrading from 1.x to 2.0

* PHP 8.2 or higher is required.
* The public API has native types. Invalid argument types raise a `TypeError`, for example
  `share()` with something that is neither a class name nor an object no longer throws `ConfigException`.
* Custom `Reflector`, `ReflectionCache` and `Executable` implementations or subclasses must update their
  signatures.
* Sharing a second, different instance of an already shared type throws `ConfigException` with code
  `Injector::E_DOUBLE_SHARE`. Sharing the same instance again is still allowed.
* A delegate must return an instance of the requested type, otherwise `make()` throws
  `InjectionException` with code `Injector::E_MAKING_FAILED`.
* `prepare()` throws `ConfigException` instead of `InjectionException` for an invalid callable.
* Invalid `@param` class definitions throw `InjectionException` with code
  `Injector::E_INVALID_DEFINE_ARGUMENT_NOT_ARRAY` or `Injector::E_INVALID_DEFINE_ARGUMENT_BAD_KEYS`.
* `ReflectionCacheApc` uses the `apcu` extension instead of the removed `apc` one.

## Basic Usage

To start using the injector, simply create a new instance of the `Auryn\Injector` ("the Injector")
class:

```php
<?php
$injector = new Auryn\Injector();
```

### Basic Instantiation

If a class doesn't specify any dependencies in its constructor signature there's little point in
using the Injector to generate it. However, for the sake of completeness consider that you can do
the following with equivalent results:

```php
<?php
$injector = new Auryn\Injector();
$obj1 = new SomeNamespace\MyClass;
$obj2 = $injector->make('SomeNamespace\MyClass');

var_dump($obj2 instanceof SomeNamespace\MyClass); // true
```

###### Concrete Type-hinted Dependencies

If a class only asks for concrete dependencies you can use the Injector to inject them without
specifying any injection definitions. For example, in the following scenario you can use the
Injector to automatically provision `MyClass` with the required `SomeDependency` and `AnotherDependency`
class instances:

```php
<?php
class SomeDependency {}

class AnotherDependency {}

class MyClass {
    public $dep1;
    public $dep2;
    public function __construct(SomeDependency $dep1, AnotherDependency $dep2) {
        $this->dep1 = $dep1;
        $this->dep2 = $dep2;
    }
}

$injector = new Auryn\Injector();
$myObj = $injector->make('MyClass');

var_dump($myObj->dep1 instanceof SomeDependency); // true
var_dump($myObj->dep2 instanceof AnotherDependency); // true
```

###### Recursive Dependency Instantiation

One of the Injector's key attributes is that it recursively traverses class dependency trees to
instantiate objects. This is just a fancy way of saying, "if you instantiate object A which asks for
object B, the Injector will instantiate any of object B's dependencies so that B can be instantiated
and provided to A". This is perhaps best understood with a simple example. Consider the following
classes in which a `Car` asks for `Engine` and the `Engine` class has concrete dependencies of its
own:

```php
<?php
class Car {
    private $engine;
    public function __construct(Engine $engine) {
        $this->engine = $engine;
    }
}

class Engine {
    private $sparkPlug;
    private $piston;
    public function __construct(SparkPlug $sparkPlug, Piston $piston) {
        $this->sparkPlug = $sparkPlug;
        $this->piston = $piston;
    }
}

$injector = new Auryn\Injector();
$car = $injector->make('Car');
var_dump($car instanceof Car); // true
```

### Injection Definitions

You may have noticed that the previous examples all demonstrated instantiation of classes with
explicit, type-hinted, concrete constructor parameters. Obviously, many of your classes won't fit
this mold. Some classes will type-hint interfaces and abstract classes. Some will specify scalar
parameters which offer no possibility of type-hinting in PHP. Still other parameters will be arrays,
etc. In such cases we need to assist the Injector by telling it exactly what we want to inject.

###### Defining Class Names for Constructor Parameters

Let's look at how to provision a class with non-concrete type-hints in its constructor signature.
Consider the following code in which a `Car` needs an `Engine` and `Engine` is an interface:

```php
<?php
interface Engine {}

class V8 implements Engine {}

class Car {
    private $engine;
    public function __construct(Engine $engine) {
        $this->engine = $engine;
    }
}
```

To instantiate a `Car` in this case, we simply need to define an injection definition for the class
ahead of time:

```php
<?php
$injector = new Auryn\Injector();
$injector->define('Car', ['engine' => 'V8']);
$car = $injector->make('Car');

var_dump($car instanceof Car); // true
```

The most important points to notice here are:

1. A custom definition is an `array` whose keys match constructor parameter names
2. The values in the definition array represent the class names to inject for the specified
   parameter key

Because the `Car` constructor parameter we needed to define was named `$engine`, our definition
specified an `engine` key whose value was the name of the class (`V8`) that we want to inject.

Custom injection definitions are only necessary on a per-parameter basis. For example, in the
following class we only need to define the injectable class for `$arg2` because `$arg1` specifies a
concrete class type-hint:

```php
<?php
class MyClass {
    private $arg1;
    private $arg2;
    public function __construct(SomeConcreteClass $arg1, SomeInterface $arg2) {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}

$injector = new Auryn\Injector();
$injector->define('MyClass', ['arg2' => 'SomeImplementationClass']);

$myObj = $injector->make('MyClass');
```

> **NOTE:** Injecting instances where an abstract class is type-hinted works in exactly the same way
as the above examples for interface type-hints.

###### Using Existing Instances in Injection Definitions

Injection definitions may also specify a pre-existing instance of the requisite class instead of the
string class name:

```php
<?php
interface SomeInterface {}

class SomeImplementation implements SomeInterface {}

class MyClass {
    private $dependency;
    public function __construct(SomeInterface $dependency) {
        $this->dependency = $dependency;
    }
}

$injector = new Auryn\Injector();
$dependencyInstance = new SomeImplementation;
$injector->define('MyClass', [':dependency' => $dependencyInstance]);

$myObj = $injector->make('MyClass');

var_dump($myObj instanceof MyClass); // true
```

> **NOTE:** Since this `define()` call is passing raw values (as evidenced by the colon `:` usage),
you can achieve the same result by omitting the array key(s) and relying on parameter order rather
than name. Like so: `$injector->define('MyClass', [$dependencyInstance]);`

###### Specifying Injection Definitions On the Fly

You may also specify injection definitions at call-time with `Auryn\Injector::make`. Consider:

```php
<?php
interface SomeInterface {}

class SomeImplementationClass implements SomeInterface {}

class MyClass {
    private $dependency;
    public function __construct(SomeInterface $dependency) {
        $this->dependency = $dependency;
    }
}

$injector = new Auryn\Injector();
$myObj = $injector->make('MyClass', ['dependency' => 'SomeImplementationClass']);

var_dump($myObj instanceof MyClass); // true
```

The above code shows how even though we haven't called  the Injector's `define` method, the
call-time specification allows us to instantiate `MyClass`.

> **NOTE:** on-the-fly instantiation definitions will override a pre-defined definition for the
specified class, but only in the context of that particular call to `Auryn\Injector::make`.

### Type-Hint Aliasing

Programming to interfaces is one of the most useful concepts in object-oriented design (OOD), and
well-designed code should type-hint interfaces whenever possible. But does this mean we have to
assign injection definitions for every class in our application to reap the benefits of abstracted
dependencies? Thankfully the answer to this question is, "NO."  The Injector accommodates this goal
by accepting "aliases". Consider:

```php
<?php
interface Engine {}
class V8 implements Engine {}
class Car {
    private $engine;
    public function __construct(Engine $engine) {
        $this->engine = $engine;
    }
}

$injector = new Auryn\Injector();

// Tell the Injector class to inject an instance of V8 any time
// it encounters an Engine type-hint
$injector->alias('Engine', 'V8');

$car = $injector->make('Car');
var_dump($car instanceof Car); // bool(true)
```

In this example we've demonstrated how to specify an alias class for any occurrence of a particular
interface or abstract class type-hint. Once an implementation is assigned, the Injector will use it
to provision any parameter with a matching type-hint.

> **IMPORTANT:** If an injection definition is defined for a parameter covered by an implementation
assignment, the definition takes precedence over the implementation.

### Non-Class Parameters

All of the previous examples have demonstrated how the Injector class instantiates parameters based
on type-hints, class name definitions and existing instances. But what happens if we want to inject
a scalar or other non-object variable into a class? First, let's establish the following behavioral
rule:

> **IMPORTANT:** The Injector assumes all named-parameter definitions are class names by default.

If you want the Injector to treat a named-parameter definition as a "raw" value and not a class name,
you must prefix the parameter name in your definition with a colon character `:`. For example,
consider the following code in which we tell the Injector to share a `PDO` database connection
instance and define its scalar constructor parameters:

```php
<?php
$injector = new Auryn\Injector();
$injector->share('PDO');
$injector->define('PDO', [
    ':dsn' => 'mysql:dbname=testdb;host=127.0.0.1',
    ':username' => 'dbuser',
    ':passwd' => 'dbpass'
]);

$db = $injector->make('PDO');
```

The colon character preceding the parameter names tells the Injector that the associated values ARE
NOT class names. If the colons had been omitted above, auryn would attempt to instantiate classes of
the names specified in the string and an exception would result. Also, note that we could just as
easily specified arrays or integers or any other data type in the above definitions. As long as the
parameter name is prefixed with a `:`, auryn will inject the value directly without attempting to
instantiate it.

> **NOTE:** As mentioned previously, since this `define()` call is passing raw values, you may opt to
assign the values by parameter order rather than name. Since PDO's first three parameters are `$dsn`,
`$username`, and `$password`, in that order, you could accomplish the same result by leaving out the
array keys, like so:
`$injector->define('PDO', ['mysql:dbname=testdb;host=127.0.0.1', 'dbuser', 'dbpass']);`

### Global Parameter Definitions

Sometimes applications may reuse the same value everywhere. However, it can be a hassle to manually
specify definitions for this sort of thing everywhere it might be used in the app. auryn mitigates
this problem by exposing the `Injector::defineParam()` method. Consider the following example ...

```php
<?php
$myUniversalValue = 42;

class MyClass {
    public $myValue;
    public function __construct($myValue) {
        $this->myValue = $myValue;
    }
}

$injector = new Auryn\Injector();
$injector->defineParam('myValue', $myUniversalValue);
$obj = $injector->make('MyClass');
var_dump($obj->myValue === 42); // bool(true)
```

Because we specified a global definition for `myValue`, all parameters that are not in some other
way defined (as below) that match the specified parameter name are auto-filled with the global value.
If a parameter matches any of the following criteria the global value is not used:

- A typehint
- A predefined injection definition
- A custom call time definition


## Advanced Usage

### Instance Sharing

One of the more ubiquitous plagues in modern OOP is the Singleton anti-pattern. Coders looking to
limit classes to a single instance often fall into the trap of using `static` Singleton
implementations for things like configuration classes and database connections. While it's often
necessary to prevent multiple instances of a class, the Singleton method spells death to testability
and should generally be avoided. `Auryn\Injector` makes sharing class instances across contexts a
triviality while allowing maximum testability and API transparency.

Let's consider how a typical problem facing object-oriented web applications is easily solved by
wiring together your application using auryn. Here, we want to inject a single database connection
instance across multiple layers of an application. We have a controller class that asks for a
DataMapper that requires a `PDO` database connection instance:

```php
<?php
class DataMapper {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
}

class MyController {
    private $mapper;
    public function __construct(DataMapper $mapper) {
        $this->mapper = $mapper;
    }
}

$db = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');

$injector = new Auryn\Injector();
$injector->share($db);

$myController = $injector->make('MyController');
```

In the above code, the `DataMapper` instance will be provisioned with the same `PDO` database
connection instance we originally shared. This example is contrived and overly simple, but the
implication should be clear:

> By sharing an instance of a class, `Auryn\Injector` will always use that instance when
> provisioning classes that type-hint the shared class.

###### A Simpler Example

Let's look at a simple proof of concept:

```php
<?php
class Person {
    public $name = 'John Snow';
}

$injector = new Auryn\Injector();
$injector->share('Person');

$person = $injector->make('Person');
var_dump($person->name); // John Snow

$person->name = 'Arya Stark';

$anotherPerson = $injector->make('Person');
var_dump($anotherPerson->name); // Arya Stark
var_dump($person === $anotherPerson); // bool(true) because it's the same instance!
```

Defining an object as shared will store the provisioned instance in the Injector's shared cache and
all future requests to the provider for an injected instance of that class will return the
originally created object. Note that in the above code, we shared the class name (`Person`)
instead of an actual instance. Sharing works with either a class name or an instance of a class.
The difference is that when you specify a class name, the Injector
will cache the shared instance the first time it is asked to create it.

> **NOTE:** Once the Injector caches a shared instance, call-time definitions passed to
`Auryn\Injector::make` will have no effect. Once shared, an instance will always be returned for
instantiations of its type until the object is un-shared or refreshed:

### Instance Proxy

In some case you need to lazy load an instantiation of a class, for example:

* your object takes a lot of time and memory to be initialized (with all dependencies)
* your object is not always used, and the instantiation overhead is avoidable

Let's look at a very basic example to demonstrate the concept of injection proxy:

```php
class Dependency
{
    public function __construct()
    {
        // Do heavy initialization here
        echo 'Dependency has been initialized' . PHP_EOL;
    }
    public function doSomething()
    {
    }
}

class MyClass
{
    public $dependency;

    public function __construct(Dependency $dependency)
    {
        $this->dependency = $dependency;
    }
}

$injector->proxy(Dependency::class); // This will be proxied
$my_class = $injector->make(MyClass::class);

// It works as expected and $my_class->dependency will be replaced with the original one only when a call happens
$my_class->dependency->doSomething();
```

In the above code we proxies `Dependency::class` and once the Injector will instantiate the `MyClass::class` a proxy
version of `Dependency::class` will be injected and `$my_class->dependency` will be replaced with the original one
only when a call happens.

> **NOTE:** Using `Auryn\Injector::delegate` will bypass the proxied class, if you want to delegate class instantiation
with your factory and you want also a proxy version of it you have to create your business logic for handling proxy.

You can find more informations about how proxy and lazy loading work in the [ProxyManager](https://github.com/Ocramius/ProxyManager) documentation.

### Instantiation Delegates

Often factory classes/methods are used to prepare an object for use after instantiation. auryn
allows you to integrate factories and builders directly into the injection process by specifying
callable instantiation delegates on a per-class basis. Let's look at a very basic example to
demonstrate the concept of injection delegates:

```php
<?php
class MyComplexClass {
    public $verification = false;
    public function doSomethingAfterInstantiation() {
        $this->verification = true;
    }
}

$complexClassFactory = function() {
    $obj = new MyComplexClass;
    $obj->doSomethingAfterInstantiation();

    return $obj;
};

$injector = new Auryn\Injector();
$injector->delegate('MyComplexClass', $complexClassFactory);

$obj = $injector->make('MyComplexClass');
var_dump($obj->verification); // bool(true)
```

In the above code we delegate instantiation of the `MyComplexClass` class to a closure,
`$complexClassFactory`. Once this delegation is made, the Injector will return the results of the
specified closure when asked to instantiate `MyComplexClass`.

###### Available Delegate Types

Any valid PHP callable may be registered as a class instantiation delegate using
`Auryn\Injector::delegate`. Additionally you may specify the name of a delegate class that
specifies an `__invoke` method and it will be automatically provisioned and have its `__invoke`
method called at delegation time. Instance methods from uninstantiated classes may also be specified
using the `['NonStaticClassName', 'factoryMethod']` construction. For example:

```php
<?php
class SomeClassWithDelegatedInstantiation {
    public $value = 0;
}
class SomeFactoryDependency {}
class MyFactory {
    private $dependency;
    function __construct(SomeFactoryDependency $dep) {
        $this->dependency = $dep;
    }
    function __invoke() {
        $obj = new SomeClassWithDelegatedInstantiation;
        $obj->value = 1;
        return $obj;
    }
    function factoryMethod() {
        $obj = new SomeClassWithDelegatedInstantiation;
        $obj->value = 2;
        return $obj;
    }
}

// Works because MyFactory specifies a magic __invoke method
$injector->delegate('SomeClassWithDelegatedInstantiation', 'MyFactory');
$obj = $injector->make('SomeClassWithDelegatedInstantiation');
var_dump($obj->value); // int(1)

// This also works
$injector->delegate('SomeClassWithDelegatedInstantiation', 'MyFactory::factoryMethod');
$obj = $injector->make('SomeClassWithDelegatedInstantiation');
$obj = $injector->make('SomeClassWithDelegatedInstantiation');
var_dump($obj->value); // int(2)
```

### Prepares and Setter Injection

Constructor injection is almost always preferable to setter injection. However, some APIs require
additional post-instantiation mutations. auryn accommodates these use cases with its
`Injector::prepare()` method. Users may register any class or interface name for post-instantiation
modification. Consider:

```php
<?php

class MyClass {
    public $myProperty = 0;
}

$injector->prepare('MyClass', function($myObj, $injector) {
    $myObj->myProperty = 42;
});

$myObj = $injector->make('MyClass');
var_dump($myObj->myProperty); // int(42)
```

While the above example is contrived, the usefulness should be clear.


### Injecting for Execution

In addition to provisioning class instances using constructors, auryn can also recursively instantiate
the parameters of any [valid PHP callable](http://php.net/manual/en/language.types.callable.php).
The following examples all work:

```php
<?php
$injector = new Auryn\Injector();
$injector->execute(function(){});
$injector->execute([$objectInstance, 'methodName']);
$injector->execute('globalFunctionName');
$injector->execute('MyStaticClass::myStaticMethod');
$injector->execute(['MyStaticClass', 'myStaticMethod']);
$injector->execute(['MyChildStaticClass', 'parent::myStaticMethod']);
$injector->execute('ClassThatHasMagicInvoke');
$injector->execute($instanceOfClassThatHasMagicInvoke);
$injector->execute('MyClass::myInstanceMethod');
```

Additionally, you can pass in the name of a class for a non-static method and the injector will
automatically provision an instance of the class (subject to any definitions or shared instances
already stored by the injector) before provisioning and invoking the specified method:

```php
<?php
class Dependency {}
class AnotherDependency {}
class Example {
    function __construct(Dependency $dep){}
    function myMethod(AnotherDependency $arg1, $arg2) {
        return $arg2;
    }
}

$injector = new Auryn\Injector();

// outputs: int(42)
var_dump($injector->execute('Example::myMethod', $args = [':arg2' => 42]));
```


### Custom make() and execute() arguments

The `$args` parameter of `Injector::make(string $name, array $args = [])` and
`Injector::execute(mixed $callableOrMethodStr, array $args = [])` passes a bespoke set of arguments for that single
creation or execution.

For a parameter named `foo` at position `i`, the first matching rule wins:

1. `$args[i]`, an integer key: the value is used as is.
2. `$args['foo']`: the value is a class name, the injector makes it.
3. `$args[':foo']` (`Injector::A_RAW . 'foo'`): the value is used as is.
4. `$args['+foo']` (`Injector::A_DELEGATE . 'foo'`): the value is a delegate, its return value is used.
5. `$args['@foo']` (`Injector::A_DEFINE . 'foo'`): the value is an array `[class-string, array $args]`, the injector
   makes that class with those arguments.
6. Otherwise the normal resolution applies: the type (aliases, shares, delegates), the global parameter definitions
   and finally the default value.

The `Auryn\Arguments` builder writes the keys with their prefixes, `define()`, `make()` and `execute()` accept it in
place of the array:

```php
use Auryn\Arguments;

$injector->define(Report::class, (new Arguments())
    ->raw('title', 'Monthly report')                    // ':title'
    ->make('logger', FileLogger::class)                 // 'logger', a list of classes for a variadic
    ->delegate('clock', static fn() => new FixedClock()) // '+clock'
    ->define('connection', Connection::class, (new Arguments())->raw('dsn', 'sqlite::memory:'))); // '@connection'
```

Every key must target a parameter, a key that matches none, for example `':tilte'` instead of `':title'`, throws
`InjectionException` with code `Injector::E_UNKNOWN_DEFINITION_KEY`. The same applies to `define()`. The `$args` of
`make()` for a delegated class are passed to the delegate, not to the constructor, and are not checked.

```php
$object = $injector->make(PrefixDefineTest::class, [
    Injector::A_DEFINE . 'pdd' => [
        PrefixDefineDependency::class,
        [Injector::A_RAW . 'message' => $message],
    ],
]);
```

Here the injector makes `PrefixDefineTest`, and creates its `$pdd` dependency, a `PrefixDefineDependency`, with
`$message` as its `message` argument.

### Dependency Resolution

`Auryn\Injector` resolves dependencies in the following order:

1. If a shared instance exists for the class in question, the shared instance will always be returned
2. If a delegate callable is assigned for a class, its return result will always be used
3. If a call-time definition is passed to `Auryn\Injector::make`, that definition will be used
4. If a pre-defined definition exists, it will be used
5. If a dependency is type-hinted, the Injector will recursively instantiate it subject to any implementations or definitions
6. If no type-hint exists and the parameter has a default value, the default value is injected
7. If a global parameter value is defined that value is used
8. Throw an exception because you did something stupid

## Example Use Cases

Dependency Injection Containers (DIC) are generally misunderstood in the PHP community. One of the
primary culprits is the misuse of such containers in the mainstream application frameworks. Often,
these frameworks warp their DICs into Service Locator anti-patterns. This is a shame because a
good DIC should be the exact opposite of a Service Locator.

###### auryn Is NOT A Service Locator!

There's a galaxy of differences between using a DIC to wire together your application versus
passing the DIC as a dependency to your objects (Service Locator). Service Locator (SL) is an
anti-pattern -- it hides class dependencies, makes code difficult to maintain and makes a liar of
your API.

When you pass a SL into your constructors it makes it difficult to determine what the class dependencies
really are. A `House` object depends on `Door` and `Window` objects. A `House` object DOES NOT depend
on an instance of `ServiceLocator` regardless of whether the `ServiceLocator` can provide `Door` and
`Window` objects.

In real life you wouldn't build a house by transporting the entire hardware store (hopefully) to
the construction site so you can access any parts you need. Instead, the foreman (`__construct()`)
asks for the specific parts that will be needed (`Door` and `Window`) and goes about procuring them.
Your objects should function in the same way; they should ask only for the specific dependencies
required to do their jobs. Giving the `House` access to the entire hardware store is at best poor
OOP style and at worst a maintainability nightmare. The takeaway here is this:

> **IMPORTANT:** do not use auryn like a Service Locator!


### Avoiding Evil Singletons

A common difficulty in web applications is limiting the number of database connection instances.
It's wasteful and slow to open up new connections each time we need to talk to a database.
Unfortunately, using singletons to limit these instances makes code brittle and hard to test. Let's
see how we can use auryn to inject the same `PDO` instance across the entire scope of our application.

Say we have a service class that requires two separate data mappers to persist information to a database:

```php
<?php

class HouseMapper {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    public function find($houseId) {
        $query = 'SELECT * FROM houses WHERE houseId = :houseId';

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':houseId', $houseId);

        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Model\\Entities\\House');
        $stmt->execute();
        $house = $stmt->fetch(PDO::FETCH_CLASS);

        if (false === $house) {
            throw new RecordNotFoundException(
                'No houses exist for the specified ID'
            );
        }

        return $house;
    }

    // more data mapper methods here ...
}

class PersonMapper {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    // data mapper methods here
}

class SomeService {
    private $houseMapper;
    private $personMapper;
    public function __construct(HouseMapper $hm, PersonMapper $pm) {
        $this->houseMapper = $hm;
        $this->personMapper = $pm;
    }
    public function doSomething() {
        // do something with the mappers
    }
}
```

In our wiring/bootstrap code, we simply instantiate the `PDO` instance once and share it in the
context of the `Injector`:

```php
<?php
$pdo = new PDO('sqlite:some_sqlite_file.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$injector = new Auryn\Injector();

$injector->share($pdo);
$mapper = $injector->make('SomeService');
```

In the above code, the DIC instantiates our service class. More importantly, the data mapper classes
it generates to do so are injected *with the same database connection instance we originally shared*.

Of course, we don't have to manually instantiate our `PDO` instance. We could just as easily seed
the container with a definition for how to create the `PDO` object and let it handle things for us:

```php
<?php
$injector->define('PDO', [
    ':dsn' => 'sqlite:some_sqlite_file.db'
]);
$injector->share('PDO');
$service = $injector->make('SomeService');
```

In the above code, the injector will pass the string definition as the `$dsn` argument in the
`PDO::__construct` method and generate the shared PDO instance automatically only if one of the
classes it instantiates requires a `PDO` instance!



### App-Bootstrapping

DICs should be used to wire together the disparate objects of your application into a cohesive
functional unit (generally at the bootstrap or front-controller stage of the application). One such
usage provides an elegant solution for one of the thorny problems in object-oriented (OO) web
applications: how to instantiate classes in a routed environment where the dependencies are not
known ahead of time.

Consider the following front controller code whose job is to:

1. Load a list of application routes and pass them to the router
2. Generate a model of the client's HTTP request
3. Route the request instance given the application's route list
4. Instantiate the routed controller and invoke a method appropriate to the HTTP request

```php
<?php

define('CONTROLLER_ROUTES', '/hard/path/to/routes.xml');

$routeLoader = new RouteLoader();
$routes = $routeLoader->loadFromXml(CONTROLLER_ROUTES);
$router = new Router($routes);

$requestDetector = new RequestDetector();
$request = $requestDetector->detectFromSuperglobal($_SERVER);

$requestUri = $request->getUri();
$requestMethod = strtolower($request->getMethod());

$injector = new Auryn\Injector();
$injector->share($request);

try {
    if (!$controllerClass = $router->route($requestUri, $requestMethod)) {
        throw new NoRouteMatchException();
    }

    $controller = $injector->make($controllerClass);
    $callableController = array($controller, $requestMethod);

    if (!is_callable($callableController)) {
        throw new MethodNotAllowedException();
    } else {
        $callableController();
    }

} catch (NoRouteMatchException $e) {
    // send 404 response
} catch (MethodNotAllowedException $e) {
    // send 405 response
} catch (Exception $e) {
    // send 500 response
}
```

And elsewhere we have various controller classes, each of which ask for their own individual
dependencies:

```php
<?php

class WidgetController {
    private $request;
    private $mapper;
    public function __construct(Request $request, WidgetDataMapper $mapper) {
        $this->request = $request;
        $this->mapper = $mapper;
    }
    public function get() {
        // do something for HTTP GET requests
    }
    public function post() {
        // do something for HTTP POST requests
    }
}
```

In the above example the auryn DIC allows us to write fully testable, fully OO controllers that ask
for their dependencies. Because the DIC recursively instantiates the dependencies of objects it
creates we have no need to pass around a Service Locator. Additionally, this example shows how we can
eliminate evil Singletons using the sharing capabilities of the auryn DIC. In the front controller
code, we share the request object so that any classes instantiated by the `Auryn\Injector` that ask
for a `Request` will receive the same instance. This feature not only helps eliminate Singletons,
but also the need for hard-to-test `static` properties.

### When app-bootstrapping by auryn is not possible

Sometimes the initialisation of the application is outside of your control, for example a WordPress plugin is
initialised by WordPress, not the other way round. You can still use auryn through a function that creates a single,
configured injector:

```php
function injector(): Auryn\Injector
{
    static $injector = null;

    if ($injector === null) {
        $injector = new Auryn\Injector();
        // Configure defines, shares, aliases and delegates here.
    }

    return $injector;
}
```

Keep calling it only from the plugin entry points, passing the injector around would turn it into a Service Locator.

## Advanced Patterns

### Variadic dependencies

A variadic parameter, always the last one, receives the list of arguments of its definition, in `define()` or in the
`$args` of `make()` and `execute()`:

```php
class Foo
{
    public function __construct(Repository ...$repositories)
    {
    }
}

// Class names, each one is made by the injector, so shares and aliases apply.
$injector->define(Foo::class, ['repositories' => [MysqlRepository::class, CacheRepository::class]]);

// Raw values.
$injector->define(Bar::class, [':names' => ['first', 'second']]);

// A delegate returning the list.
$injector->define(Foo::class, ['+repositories' => static fn() => $locator->repositoriesFor(Foo::class)]);
```

The keys of the list are ignored and a single value is a list of one. A raw array for an `array ...$items` parameter
is spread too, wrap it in another array to pass it as a single argument. Without a definition the type is resolved
as for any other parameter, so a variadic interface needs an alias.

### Context objects and multiple instances of the same type

Sometimes a class needs many instances of the same type, for example a job that moves data from the live database
to the archive database:

```php
class DataArchiver
{
    public function __construct(private PDO $liveDb, private PDO $archiveDb)
    {
    }
}
```

For small projects, more specific types work, each one created by its own delegate:

```php
class LivePDO extends PDO {}
class ArchivePDO extends PDO {}

class DataArchiver
{
    public function __construct(private LivePDO $liveDb, private ArchivePDO $archiveDb)
    {
    }
}
```

For larger projects the [Encapsulate Context](https://www.allankelly.net/static/patterns/encapsulatecontext.pdf)
pattern scales better: a specific type holds everything a business problem needs, and it is wired explicitly.

```php
final class DataArchiverContext
{
    public function __construct(private PDO $liveDb, private PDO $archiveDb)
    {
    }

    public function liveDb(): PDO
    {
        return $this->liveDb;
    }

    public function archiveDb(): PDO
    {
        return $this->archiveDb;
    }
}

class DataArchiver
{
    public function __construct(private DataArchiverContext $context)
    {
    }
}

$injector->delegate(
    DataArchiverContext::class,
    static fn(): DataArchiverContext => new DataArchiverContext(createLiveDb(), createArchiveDb())
);
```

With encapsulated contexts you can see where a context is used, which types it holds and how it is created, which
makes large applications easier to maintain.
