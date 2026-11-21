<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

final class Credentials
{
    public function __construct(public string $user)
    {
    }
}

final class Connection
{
    public function __construct(public Credentials $credentials, public string $dsn, public int $timeout = 5)
    {
    }
}

final class Report
{
    public function __construct(public Connection $connection, public string $title)
    {
    }
}

$injector = new Auryn\Injector();

// ':name' passes a raw value, '@name' builds a dependency with its own arguments.
$injector->define(Report::class, [
    ':title' => 'Monthly report',
    '@connection' => [Connection::class, [
        ':dsn' => 'sqlite::memory:',
        '+credentials' => static fn() => new Credentials('reporter'),
    ]],
]);

$report = $injector->make(Report::class);
print sprintf(
    '%s from %s as %s, timeout %d',
    $report->title,
    $report->connection->dsn,
    $report->connection->credentials->user,
    $report->connection->timeout
) . PHP_EOL;

// defineParam() is a global definition for every parameter with that name.
$injector->defineParam('timeout', 30);
$injector->define(Connection::class, [':dsn' => 'sqlite::memory:', '+credentials' => static fn() => new Credentials('admin')]);
print 'Global timeout ' . $injector->make(Connection::class)->timeout . PHP_EOL;
