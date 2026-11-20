<?php

namespace Auryn;

use PHPUnit\Framework\TestCase;

class InjectionExceptionTest extends TestCase
{
    public function testInvalidCallableArrayWithoutMethodUsesTheGenericMessage()
    {
        $exception = InjectionException::fromInvalidCallable([], ['SomeClass']);

        $this->assertSame(Injector::M_INVOKABLE, $exception->getMessage());
    }

    public function testInvalidCallableArrayIsDescribedInTheMessage()
    {
        $exception = InjectionException::fromInvalidCallable([], ['SomeClass', 'someMethod']);

        $this->assertStringContainsString("Invalid callable was 'SomeClass::someMethod'", $exception->getMessage());
    }
}
