<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->make(['id' => 'test-user']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
});

test('old dashboard path redirects to root dashboard', function () {
    $user = User::factory()->make(['id' => 'test-user']);
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertRedirect('/');
});
