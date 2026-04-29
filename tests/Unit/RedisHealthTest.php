<?php

use App\Support\RedisHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

uses(TestCase::class);

test('redis is considered available when queue and cache do not use redis', function () {
    config(['queue.default' => 'sync', 'cache.default' => 'array']);

    expect(RedisHealth::isAvailable())->toBeTrue();
});

test('redis is available when ping succeeds', function () {
    config(['queue.default' => 'redis']);

    Cache::store('file')->forget('_redis_health');
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    expect(RedisHealth::isAvailable())->toBeTrue();
});

test('redis is unavailable when ping throws', function () {
    config(['queue.default' => 'redis']);

    Cache::store('file')->forget('_redis_health');
    Redis::shouldReceive('ping')->once()->andThrow(new RuntimeException('Connection refused'));

    expect(RedisHealth::isAvailable())->toBeFalse();
});

test('redis health result is cached for subsequent calls', function () {
    config(['queue.default' => 'redis']);

    Cache::store('file')->forget('_redis_health');
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    RedisHealth::isAvailable();
    RedisHealth::isAvailable(); // should use cached result, not call ping again
});
