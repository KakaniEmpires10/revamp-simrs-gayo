<?php

use App\Models\User;

test('auth model uses the legacy uxui users table', function () {
    $user = new User;

    expect($user->getTable())->toBe('uxui_users')
        ->and($user->getKeyName())->toBe('id')
        ->and($user->getIncrementing())->toBeFalse()
        ->and($user->getKeyType())->toBe('string');
});

test('fortify is configured for legacy user id login', function () {
    expect(config('fortify.username'))->toBe('id_user')
        ->and(config('fortify.lowercase_usernames'))->toBeFalse()
        ->and(config('fortify.features'))->toBe([]);
});

test('redis and predis are the default cache and session backend', function () {
    expect(config('cache.stores.redis.driver'))->toBe('redis')
        ->and(config('session.connection'))->toBe('default')
        ->and(config('database.redis.client'))->toBe('predis')
        ->and(file_get_contents(base_path('.env.example')))->toContain('CACHE_STORE=redis')
        ->toContain('SESSION_DRIVER=redis')
        ->toContain('REDIS_CLIENT=predis');
});
