<?php

namespace Auryn;

class ReflectionCacheApc implements ReflectionCache
{
    private ReflectionCache $localCache;
    private int $timeToLive = 5;

    public function __construct(?ReflectionCache $localCache = null)
    {
        $this->localCache = $localCache ?: new ReflectionCacheArray();
    }

    public function setTimeToLive(int $seconds): static
    {
        $this->timeToLive = ($seconds > 0) ? $seconds : $this->timeToLive;

        return $this;
    }

    public function fetch(string $key): mixed
    {
        $localData = $this->localCache->fetch($key);

        if ($localData != false) {
            return $localData;
        }

        $success = false;
        $data = apcu_fetch($key, $success);

        return $success ? $data : false;
    }

    public function store(string $key, mixed $data): void
    {
        $this->localCache->store($key, $data);
        apcu_store($key, $data, $this->timeToLive);
    }
}
