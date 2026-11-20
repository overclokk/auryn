<?php

namespace Auryn;

class ReflectionCacheArray implements ReflectionCache
{
    private $cache = [];

    public function fetch(string $key): mixed
    {
        // The additional isset() check here improves performance but we also
        // need array_key_exists() because some cached values === NULL.
        return (isset($this->cache[$key]) || array_key_exists($key, $this->cache))
            ? $this->cache[$key]
            : false;
    }

    public function store(string $key, mixed $data): void
    {
        $this->cache[$key] = $data;
    }
}
