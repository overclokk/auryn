<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

interface LoggerAware
{
    public function setLogger(Logger $logger): void;
}

final class Logger
{
    public function __construct(public string $channel = 'app')
    {
    }
}

final class Mailer implements LoggerAware
{
    public ?Logger $logger = null;
    public string $from = '';

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}

$injector = new Auryn\Injector();

// A prepare for an interface runs for every class implementing it, setter injection.
$injector->prepare(LoggerAware::class, static function (LoggerAware $object, Auryn\Injector $injector): void {
    $object->setLogger($injector->make(Logger::class));
});

// A prepare for a class runs after it is made.
$injector->prepare(Mailer::class, static function (Mailer $mailer): void {
    $mailer->from = 'noreply@example.com';
});

$mailer = $injector->make(Mailer::class);
print 'Logger channel: ' . $mailer->logger?->channel . PHP_EOL;
print 'From: ' . $mailer->from . PHP_EOL;
