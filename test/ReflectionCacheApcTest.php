<?php

declare(strict_types=1);

namespace Auryn;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('apcu')]
class ReflectionCacheApcTest extends TestCase
{
    protected function setUp(): void
    {
        if (!filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('apc.enable_cli must be enabled');
        }

        apcu_clear_cache();
    }

    public function testStoredDataIsFetchedFromApcuWhenTheLocalCacheIsEmpty(): void
    {
        (new ReflectionCacheApc())->store('auryn.test.key', 'value');

        $this->assertSame('value', (new ReflectionCacheApc())->fetch('auryn.test.key'));
    }

    public function testFetchReturnsFalseForMissingKeys(): void
    {
        $this->assertFalse((new ReflectionCacheApc())->fetch('auryn.test.missing'));
    }
}
