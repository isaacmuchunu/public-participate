<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('failed login attempt increments counter', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 0,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(1);
    expect($user->locked_until)->toBeNull();
});

test('multiple failed login attempts increment counter', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 0,
    ]);

    // Attempt 1
    $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    // Attempt 2
    $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    // Attempt 3
    $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(3);
    expect($user->locked_until)->toBeNull();
});

test('account locks after five failed login attempts', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 0,
    ]);

    // Attempt 5 failed logins
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(5);
    expect($user->locked_until)->not->toBeNull();
    expect($user->locked_until->isFuture())->toBeTrue();
});

test('locked account cannot login with correct password', function () {
    $user = User::factory()->create([
        'email' => 'locked@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 5,
        'locked_until' => now()->addMinutes(15),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'locked@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('locked account shows appropriate error message', function () {
    $user = User::factory()->create([
        'email' => 'locked@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 5,
        'locked_until' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'locked@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors('email');
    $errors = session('errors');
    expect($errors->get('email')[0])->toContain('temporarily locked');
});

test('lockout expires after specified time', function () {
    $user = User::factory()->create([
        'email' => 'expired@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 5,
        'locked_until' => now()->subMinutes(1), // Already expired
    ]);

    $response = $this->post(route('login'), [
        'email' => 'expired@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(0);
    expect($user->locked_until)->toBeNull();
});

test('successful login resets failed attempt counter', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 3,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'reset@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(0);
    expect($user->locked_until)->toBeNull();
});

test('lockout duration is fifteen minutes', function () {
    $user = User::factory()->create([
        'email' => 'duration@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 0,
    ]);

    $beforeTime = now();

    // Trigger 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'duration@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $afterTime = now();

    $user->refresh();

    // Lockout should be approximately 15 minutes from now
    $expectedLockout = now()->addMinutes(15);

    expect($user->locked_until->greaterThan($beforeTime->addMinutes(14)))->toBeTrue();
    expect($user->locked_until->lessThan($afterTime->addMinutes(16)))->toBeTrue();
});

test('failed attempts for non-existent user do not cause errors', function () {
    $response = $this->post(route('login'), [
        'email' => 'nonexistent@example.com',
        'password' => 'any-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    // Verify no user was created
    expect(User::where('email', 'nonexistent@example.com')->exists())->toBeFalse();
});

test('middleware clears expired lockout automatically', function () {
    $user = User::factory()->create([
        'email' => 'autoclear@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 5,
        'locked_until' => now()->subMinute(), // Expired
    ]);

    // Login the user to trigger middleware
    auth()->login($user);

    // Make an authenticated request to trigger CheckUserNotSuspended middleware
    $response = $this->actingAs($user)->get(route('dashboard'));

    $user->refresh();
    expect($user->failed_login_attempts)->toBe(0);
    expect($user->locked_until)->toBeNull();
});

test('lockout prevents brute force attacks', function () {
    $user = User::factory()->create([
        'email' => 'bruteforce@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 0,
    ]);

    // Simulate brute force attack - 10 failed attempts
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('login'), [
            'email' => 'bruteforce@example.com',
            'password' => 'wrong-password-'.$i,
        ]);
    }

    $user->refresh();

    // Account should be locked
    expect($user->locked_until)->not->toBeNull();
    expect($user->locked_until->isFuture())->toBeTrue();

    // Further attempts should fail immediately
    $response = $this->post(route('login'), [
        'email' => 'bruteforce@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('different users have independent lockout counters', function () {
    $user1 = User::factory()->create([
        'email' => 'user1@example.com',
        'password' => Hash::make('password1'),
    ]);

    $user2 = User::factory()->create([
        'email' => 'user2@example.com',
        'password' => Hash::make('password2'),
    ]);

    // User 1 fails 3 times
    for ($i = 0; $i < 3; $i++) {
        $this->post(route('login'), [
            'email' => 'user1@example.com',
            'password' => 'wrong',
        ]);
    }

    // User 2 fails 2 times
    for ($i = 0; $i < 2; $i++) {
        $this->post(route('login'), [
            'email' => 'user2@example.com',
            'password' => 'wrong',
        ]);
    }

    $user1->refresh();
    $user2->refresh();

    expect($user1->failed_login_attempts)->toBe(3);
    expect($user2->failed_login_attempts)->toBe(2);
    expect($user1->locked_until)->toBeNull();
    expect($user2->locked_until)->toBeNull();
});

test('locked user is logged out by middleware', function () {
    $user = User::factory()->create([
        'email' => 'middleware@example.com',
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 5,
        'locked_until' => now()->addMinutes(10),
    ]);

    // Manually log in the user (simulating a session that became locked)
    auth()->login($user);
    $this->assertAuthenticatedAs($user);

    // Make a request that triggers the middleware
    $response = $this->actingAs($user)->get(route('dashboard'));

    // Should be redirected to login
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});
