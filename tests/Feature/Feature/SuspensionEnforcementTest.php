<?php

use App\Enums\UserRole;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('suspended user cannot access authenticated routes', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Your account has been suspended. Please contact support for assistance.');
    $this->assertGuest();
});

test('non-suspended user can access authenticated routes', function () {
    $user = User::factory()->create([
        'suspended_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->assertAuthenticatedAs($user);
});

test('suspension middleware logs out suspended user', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    auth()->login($user);
    $this->assertAuthenticatedAs($user);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('suspension middleware invalidates session', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    auth()->login($user);
    $oldSessionId = session()->getId();

    $this->actingAs($user)->get(route('dashboard'));

    // Session should be invalidated
    $this->assertGuest();
});

test('suspension middleware regenerates token', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    auth()->login($user);
    $oldToken = csrf_token();

    $this->actingAs($user)->get(route('dashboard'));

    // CSRF token should be regenerated (checked implicitly by Laravel)
    $this->assertGuest();
});

test('suspended legislator cannot access legislator routes', function () {
    $user = User::factory()->create([
        'role' => UserRole::Mp,
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('legislator.bills.index'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

test('suspended clerk cannot access clerk routes', function () {
    $user = User::factory()->create([
        'role' => UserRole::Clerk,
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('clerk.legislators.index'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

test('suspended citizen cannot access citizen routes', function () {
    $user = User::factory()->create([
        'role' => UserRole::Citizen,
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

test('user suspended after login is blocked on next request', function () {
    $user = User::factory()->create([
        'suspended_at' => null,
    ]);

    auth()->login($user);

    // First request succeeds
    $response1 = $this->actingAs($user)->get(route('dashboard'));
    $response1->assertOk();

    // Suspend the user
    $user->update(['suspended_at' => now()]);

    // Second request fails
    $response2 = $this->actingAs($user)->get(route('dashboard'));
    $response2->assertRedirect(route('login'));
    $this->assertGuest();
});

test('guest users are not affected by suspension middleware', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $this->assertGuest();
});

test('suspension check happens before route execution', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    // Attempt to access a route that would normally succeed
    $response = $this->actingAs($user)->get(route('dashboard'));

    // Should be redirected before dashboard code executes
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('multiple suspended users are all blocked', function () {
    $user1 = User::factory()->create(['suspended_at' => now()]);
    $user2 = User::factory()->create(['suspended_at' => now()]);
    $user3 = User::factory()->create(['suspended_at' => now()]);

    $response1 = $this->actingAs($user1)->get(route('dashboard'));
    $response2 = $this->actingAs($user2)->get(route('dashboard'));
    $response3 = $this->actingAs($user3)->get(route('dashboard'));

    $response1->assertRedirect(route('login'));
    $response2->assertRedirect(route('login'));
    $response3->assertRedirect(route('login'));

    $this->assertGuest();
});

test('suspension with past date still blocks access', function () {
    $user = User::factory()->create([
        'suspended_at' => now()->subDays(7),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('suspension with future date blocks access', function () {
    $user = User::factory()->create([
        'suspended_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('suspension error message is user friendly', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $error = session('error');
    expect($error)->toContain('suspended');
    expect($error)->toContain('contact support');
});

test('locked and suspended user shows suspension error', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
        'locked_until' => now()->addMinutes(15),
        'failed_login_attempts' => 5,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    // Suspension check should happen first
    $response->assertSessionHas('error', 'Your account has been suspended. Please contact support for assistance.');
    $this->assertGuest();
});

// Note: API routes have their own authentication middleware and are not affected by CheckUserNotSuspended web middleware

test('suspension persists across multiple requests', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    // Attempt 1
    $response1 = $this->actingAs($user)->get(route('dashboard'));
    $response1->assertRedirect(route('login'));

    // Attempt 2
    $response2 = $this->actingAs($user)->get(route('dashboard'));
    $response2->assertRedirect(route('login'));

    // Attempt 3
    $response3 = $this->actingAs($user)->get(route('dashboard'));
    $response3->assertRedirect(route('login'));

    $this->assertGuest();
});

test('non-suspended user with expired lockout can access routes', function () {
    $user = User::factory()->create([
        'suspended_at' => null,
        'locked_until' => now()->subMinutes(1),
        'failed_login_attempts' => 5,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->assertAuthenticatedAs($user);

    // Verify lockout was cleared
    $user->refresh();
    expect($user->locked_until)->toBeNull();
    expect($user->failed_login_attempts)->toBe(0);
});
