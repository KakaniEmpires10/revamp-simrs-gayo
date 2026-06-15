<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Requests\Settings\FeedbackPreferenceUpdateRequest;
use App\Models\Settings\UserSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

test('feedback preference request allows only supported modes', function () {
    $request = new FeedbackPreferenceUpdateRequest;
    $rules = $request->rules();

    expect(Validator::make(['feedback_mode' => 'alert'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['feedback_mode' => 'toast'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['feedback_mode' => 'modal'], $rules)->passes())->toBeFalse();
});

test('feedback mode default is alert', function () {
    expect(UserSetting::DefaultFeedbackMode)->toBe('alert');
});

test('user settings require user id for per user values', function () {
    expect(UserSetting::feedbackMode(null))->toBe('alert');

    UserSetting::setValue(null, 'tampilan_tabel', 'compact');
})->throws(InvalidArgumentException::class, 'User ID dan key wajib diisi untuk user setting.');

test('user settings ignore unsupported feedback mode', function () {
    expect(UserSetting::setFeedbackMode(null, 'modal'));
})->throws(InvalidArgumentException::class, 'User ID dan key wajib diisi untuk user setting.');

test('feedback preference request requires authenticated user', function () {
    $request = FeedbackPreferenceUpdateRequest::create('/settings/feedback', 'PATCH');

    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();

    $request->setUserResolver(fn () => new User([
        'id' => 'USR001',
        'id_user' => 'USR001',
        'name' => 'Petugas Test',
        'type_user' => 'petugas',
    ]));

    expect($request->authorize())->toBeTrue();
});

test('platform detection separates touch devices from desktop', function () {
    $middleware = new HandleInertiaRequests;
    $method = (new ReflectionClass($middleware))->getMethod('detectPlatform');
    $method->setAccessible(true);

    $desktopRequest = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);
    $touchRequest = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile',
    ]);

    expect($method->invoke($middleware, $desktopRequest))->toBe('desktop')
        ->and($method->invoke($middleware, $touchRequest))->toBe('touch');
});
