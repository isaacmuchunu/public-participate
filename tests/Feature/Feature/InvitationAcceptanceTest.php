<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('invitation acceptance screen can be rendered with valid token', function () {
    $user = User::factory()->create([
        'role' => UserRole::Mp,
        'invitation_token' => 'valid-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $response = $this->get(route('invitation.show', ['token' => 'valid-token-12345']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('auth/AcceptInvitation')
        ->has('token')
        ->has('user', fn ($user) => $user
            ->has('name')
            ->has('email')
            ->has('role')
        )
    );
});

test('invitation acceptance screen redirects with error for invalid token', function () {
    $response = $this->get(route('invitation.show', ['token' => 'invalid-token']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Invalid or expired invitation link.');
});

test('invitation acceptance screen redirects with error for used token', function () {
    $user = User::factory()->create([
        'role' => UserRole::Senator,
        'invitation_token' => 'used-token-12345',
        'invitation_used_at' => now()->subDays(1),
        'email_verified_at' => now()->subDays(1),
    ]);

    $response = $this->get(route('invitation.show', ['token' => 'used-token-12345']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Invalid or expired invitation link.');
});

test('invitation acceptance screen redirects with error for verified user', function () {
    $user = User::factory()->create([
        'role' => UserRole::Clerk,
        'invitation_token' => 'verified-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => now()->subDays(1),
    ]);

    $response = $this->get(route('invitation.show', ['token' => 'verified-token-12345']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Invalid or expired invitation link.');
});

test('legislator can accept invitation and set password', function () {
    $user = User::factory()->create([
        'role' => UserRole::Mp,
        'invitation_token' => 'accept-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'accept-token-12345']), [
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    $response->assertRedirect(route('legislator.bills.index'));
    $response->assertSessionHas('success', 'Welcome! Your account has been activated successfully.');

    $user->refresh();

    expect($user->invitation_used_at)->not->toBeNull();
    expect($user->invitation_token)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('NewPassword123', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('clerk can accept invitation and is redirected to legislators page', function () {
    $user = User::factory()->create([
        'role' => UserRole::Clerk,
        'invitation_token' => 'clerk-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'clerk-token-12345']), [
        'password' => 'ClerkPassword123',
        'password_confirmation' => 'ClerkPassword123',
    ]);

    $response->assertRedirect(route('clerk.legislators.index'));
    $response->assertSessionHas('success', 'Welcome! Your account has been activated successfully.');

    $this->assertAuthenticatedAs($user);
});

test('senator can accept invitation and is redirected to bills page', function () {
    $user = User::factory()->create([
        'role' => UserRole::Senator,
        'invitation_token' => 'senator-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'senator-token-12345']), [
        'password' => 'SenatorPassword123',
        'password_confirmation' => 'SenatorPassword123',
    ]);

    $response->assertRedirect(route('legislator.bills.index'));
    $response->assertSessionHas('success', 'Welcome! Your account has been activated successfully.');

    $this->assertAuthenticatedAs($user);
});

test('invitation acceptance fails with invalid token', function () {
    $response = $this->post(route('invitation.accept', ['token' => 'invalid-token']), [
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Invalid or expired invitation link.');

    $this->assertGuest();
});

test('invitation acceptance fails when token is already used', function () {
    $user = User::factory()->create([
        'role' => UserRole::Mp,
        'invitation_token' => 'used-token-67890',
        'invitation_used_at' => now()->subDays(1),
        'email_verified_at' => now()->subDays(1),
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'used-token-67890']), [
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Invalid or expired invitation link.');

    $this->assertGuest();
});

test('invitation acceptance validates password requirements', function () {
    $user = User::factory()->create([
        'role' => UserRole::Mp,
        'invitation_token' => 'validation-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'validation-token-12345']), [
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('invitation acceptance requires password confirmation', function () {
    $user = User::factory()->create([
        'role' => UserRole::Senator,
        'invitation_token' => 'confirm-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'confirm-token-12345']), [
        'password' => 'ValidPassword123',
        'password_confirmation' => 'DifferentPassword123',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('invitation token prevents reuse attack', function () {
    $user = User::factory()->create([
        'role' => UserRole::Mp,
        'invitation_token' => 'reuse-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    // First acceptance - should succeed
    $response1 = $this->post(route('invitation.accept', ['token' => 'reuse-token-12345']), [
        'password' => 'FirstPassword123',
        'password_confirmation' => 'FirstPassword123',
    ]);

    $response1->assertRedirect(route('legislator.bills.index'));
    $this->assertAuthenticatedAs($user);

    // Logout
    auth()->logout();

    // Attempt to reuse same token - should fail
    $response2 = $this->post(route('invitation.accept', ['token' => 'reuse-token-12345']), [
        'password' => 'SecondPassword123',
        'password_confirmation' => 'SecondPassword123',
    ]);

    $response2->assertRedirect(route('login'));
    $response2->assertSessionHas('error', 'Invalid or expired invitation link.');
    $this->assertGuest();
});

test('session is regenerated after invitation acceptance', function () {
    $user = User::factory()->create([
        'role' => UserRole::Clerk,
        'invitation_token' => 'session-token-12345',
        'invitation_used_at' => null,
        'email_verified_at' => null,
    ]);

    $oldSessionId = session()->getId();

    $response = $this->post(route('invitation.accept', ['token' => 'session-token-12345']), [
        'password' => 'SessionPassword123',
        'password_confirmation' => 'SessionPassword123',
    ]);

    $newSessionId = session()->getId();

    expect($oldSessionId)->not->toBe($newSessionId);
    $this->assertAuthenticatedAs($user);
});
