<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/test',
    ])
    ->withPhpSets(php82: true)
    ->withComposerBased(phpunit: true)
    ->withAttributesSets(phpunit: true)
    ->withSkip([
        // Auryn resolves definitions by parameter name, promotion can rename parameters.
        ClassPropertyAssignToConstructorPromotionRector::class,
        // Lazy loading proxies cannot initialize readonly properties.
        ReadOnlyPropertyRector::class,
        // Tests use array callables on purpose, Auryn resolves aliases on them.
        ArrayToFirstClassCallableRector::class => [__DIR__ . '/test'],
    ]);
