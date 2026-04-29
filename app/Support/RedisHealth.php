<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RedisHealth
{
    /**
     * Returns true if Redis is reachable (or not required by the current config).
     * Result is cached for 60 seconds in the file store so a Redis outage doesn't
     * flood the server with connection attempts on every request.
     */
    public static function isAvailable(): bool
    {
        if (! static::isRequired()) {
            return true;
        }

        return Cache::store('file')->remember('_redis_health', 60, function (): bool {
            try {
                Redis::ping();

                return true;
            } catch (\Throwable) {
                return false;
            }
        });
    }

    private static function isRequired(): bool
    {
        return config('queue.default') === 'redis'
            || config('cache.default') === 'redis';
    }
}
