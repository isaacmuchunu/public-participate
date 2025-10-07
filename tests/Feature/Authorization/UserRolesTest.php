<?php

use App\Models\User;

it('creates user with citizen role', function () {
    $user = User::factory()->citizen()->create();

    expect($user->role->value)->toBe('citizen');
    expect($user->legislative_house)->toBeNull();
});

it('creates user with legislator role', function () {
    $user = User::factory()->legislator('senate')->create();

    expect($user->role->value)->toBe('legislator');
    expect($user->legislative_house)->toBe('senate');
});

it('creates user with national assembly legislator role', function () {
    $user = User::factory()->legislator('national_assembly')->create();

    expect($user->role->value)->toBe('legislator');
    expect($user->legislative_house)->toBe('national_assembly');
});

it('creates user with clerk role', function () {
    $user = User::factory()->clerk()->create();

    expect($user->role->value)->toBe('clerk');
    expect($user->legislative_house)->toBeNull();
});

it('creates user with admin role', function () {
    $user = User::factory()->admin()->create();

    expect($user->role->value)->toBe('admin');
    expect($user->legislative_house)->toBeNull();
});

it('citizen has location information', function () {
    $user = User::factory()->citizen()->create();

    expect($user->county_id)->not->toBeNull();
    expect($user->constituency_id)->not->toBeNull();
    expect($user->ward_id)->not->toBeNull();
});

it('user has phone verification fields', function () {
    $user = User::factory()->create();

    expect($user->phone)->not->toBeNull();
    expect($user->phone_verified_at)->not->toBeNull();
    expect($user->otp_verified_at)->not->toBeNull();
});

it('user has national id', function () {
    $user = User::factory()->create();

    expect($user->national_id)->not->toBeNull();
    expect($user->national_id)->toHaveLength(10);
});

it('user is verified by default in factory', function () {
    $user = User::factory()->create();

    expect($user->is_verified)->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
});

it('can create unverified user', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

it('different users have unique phone numbers', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->phone)->not->toBe($user2->phone);
});

it('different users have unique national ids', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->national_id)->not->toBe($user2->national_id);
});

it('different users have unique emails', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->email)->not->toBe($user2->email);
});
