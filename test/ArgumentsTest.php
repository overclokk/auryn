<?php

declare(strict_types=1);

namespace Auryn;

use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testEachMethodBuildsTheMatchingKey(): void
    {
        $delegate = static fn () => new DepImplementation();

        $arguments = (new Arguments())
            ->position(0, 'first')
            ->make('dependency', DepImplementation::class)
            ->raw('title', 'value')
            ->delegate('factory', $delegate)
            ->define('nested', NeedsTitleAndDependency::class, (new Arguments())->raw('title', 'nested'));

        $this->assertSame([
            0 => 'first',
            'dependency' => DepImplementation::class,
            ':title' => 'value',
            '+factory' => $delegate,
            '@nested' => [NeedsTitleAndDependency::class, [':title' => 'nested']],
        ], $arguments->toArray());
    }

    public function testDefineAcceptsAPlainArray(): void
    {
        $arguments = (new Arguments())->define('nested', NeedsTitleAndDependency::class, [':title' => 'nested']);

        $this->assertSame(['@nested' => [NeedsTitleAndDependency::class, [':title' => 'nested']]], $arguments->toArray());
    }

    public function testMakeAcceptsAListOfClassesForVariadics(): void
    {
        $arguments = (new Arguments())->make('dependencies', [FirstVariadicDependency::class, SecondVariadicDependency::class]);

        $this->assertSame(
            ['dependencies' => [FirstVariadicDependency::class, SecondVariadicDependency::class]],
            $arguments->toArray()
        );
    }

    public function testArgumentsAreImmutable(): void
    {
        $empty = new Arguments();
        $withTitle = $empty->raw('title', 'value');

        $this->assertSame([], $empty->toArray());
        $this->assertNotSame($empty, $withTitle);
    }

    public function testInjectorAcceptsArguments(): void
    {
        $injector = new Injector();
        $injector->define(NeedsTitleAndDependency::class, (new Arguments())->raw('title', 'defined'));

        $this->assertSame('defined', $injector->make(NeedsTitleAndDependency::class)->title);
        $this->assertSame(
            'made',
            $injector->make(NeedsTitleAndDependency::class, (new Arguments())->raw('title', 'made'))->title
        );
        $this->assertSame(
            'executed',
            $injector->execute(static fn (string $name) => $name, (new Arguments())->raw('name', 'executed'))
        );
    }

    public function testPositionsKeepTheirIndex(): void
    {
        $arguments = (new Arguments())->raw('title', 'value')->position(2, 'third');

        $this->assertSame([':title' => 'value', 2 => 'third'], $arguments->toArray());
    }
}
