<?php

declare(strict_types=1);

namespace Auryn;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every script in examples/ runs in its own PHP process and prints a known output.
 */
class ExamplesTest extends TestCase
{
    private const EXPECTED_OUTPUT = [
        'aliases_and_sharing.php' => "Clock: 2026-01-01\nSame logger: true\nShared instance: true\nDouble share rejected: yes\n",
        'definitions.php' => "Monthly report from sqlite::memory: as reporter, timeout 5\nGlobal timeout 30\n",
        'delegate_params.php' => "foobar\n",
        'execute.php' => "Hello closure\nHello method string\nHello array callable\nVersion 2.x\nHello from an invokable\n",
        'make_with_concrete_dependency.php' => "foobar\n",
        'prepare.php' => "Logger channel: app\nFrom: noreply@example.com\n",
        'proxy.php' => "Configuration complete.\n"
            . "\$car is an instance of Car: bool(true)\n"
            . "Note: Constructor of Car has not been called, yet.\n"
            . "turnRight call the Car constructor.\n"
            . "Car::__construct\nCar::turnRight\n"
            . "turnLeft call the V8 constructor.\n"
            . "V8::__construct\nV8::turnOn\nCar::turnLeft\n42\n"
            . "V8::turnOn\nCar::turnLeft\n42\n",
        'variadic.php' => "mysql, cache\nHello Alice and Bob\ncache\ncache, mysql\n",
    ];

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideExamples(): iterable
    {
        foreach (self::EXPECTED_OUTPUT as $file => $output) {
            yield $file => [$file, $output];
        }
    }

    #[DataProvider('provideExamples')]
    public function testExamplePrintsTheExpectedOutput(string $file, string $expected): void
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/examples/' . $file) . ' 2>&1';

        exec($command, $lines, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $lines));
        $this->assertSame($expected, implode("\n", $lines) . "\n");
    }

    public function testEveryExampleHasAnExpectedOutput(): void
    {
        $files = array_map('basename', glob(dirname(__DIR__) . '/examples/*.php') ?: []);
        sort($files);

        $this->assertSame(array_keys(self::EXPECTED_OUTPUT), $files);
    }
}
