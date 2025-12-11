<?php

namespace MartinLechene\AuditSuite\Services;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
    private string $prefix = 'audit_suite:';
    private int $ttl;

    public function __construct()
    {
        $this->ttl = config('audit-suite.cache.ttl', 3600);
    }

    public function remember(string $key, callable $callback)
    {
        return Cache::remember(
            $this->prefix . $key,
            $this->ttl,
            $callback
        );
    }

    public function forget(string $key): bool
    {
        return Cache::forget($this->prefix . $key);
    }

    public function flush(): bool
    {
        // Note: This would flush all cache, might want to be more specific
        return Cache::flush();
    }

    public function get(string $key, $default = null)
    {
        return Cache::get($this->prefix . $key, $default);
    }

    public function put(string $key, $value, ?int $ttl = null): bool
    {
        return Cache::put($this->prefix . $key, $value, $ttl ?? $this->ttl);
    }
}

