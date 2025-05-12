<?php

use App\Models\User;
use App\Models\UserSession;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('users can view their active sessions', function () {
    $user = User::factory()->create();
    UserSession::factory()->count(2)->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = $this->get(route('sessions.index'));

    $response->assertInertia(fn (Assert $page) => $page->component('settings/Sessions')->has('sessions', 2));
});

test('users can revoke a non current session', function () {
    $user = User::factory()->create();
    $session = UserSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $token = 'test-token';
    $this->withSession(['_token' => $token]);

    $response = $this->withCookie('laravel_session', session()->getId())
        ->delete(route('sessions.destroy', $session), ['_token' => $token]);

    $response->assertSessionHas('status', 'session-revoked');
    $this->assertDatabaseMissing('user_sessions', ['id' => $session->id]);
});

test('revoking the current session logs the user out', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $token = 'test-token';
    $this->withSession(['_token' => $token]);

    $this->get(route('sessions.index'));

    $currentSessionId = session()->getId();
    $session = UserSession::where('session_id', $currentSessionId)->firstOrFail();

    $response = $this->withCookie('laravel_session', $currentSessionId)
        ->delete(route('sessions.destroy', $session), ['_token' => $token]);

    $response->assertRedirect(route('login'));
    $this->assertGuest();
    $this->assertDatabaseMissing('user_sessions', ['id' => $session->id]);
});
