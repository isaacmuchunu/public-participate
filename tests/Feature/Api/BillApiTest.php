<?php

use App\Models\Bill;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('lists bills for authenticated user', function () {
    $user = User::factory()->citizen()->create();
    Bill::factory()->count(3)->openForParticipation()->create();

    $response = actingAs($user)->getJson('/api/v1/bills');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'title', 'bill_number', 'status'],
        ],
    ]);
});

it('shows bill details', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create();

    $response = actingAs($user)->getJson("/api/v1/bills/{$bill->id}");

    $response->assertSuccessful();
    $response->assertJson([
        'data' => [
            'id' => $bill->id,
            'title' => $bill->title,
            'bill_number' => $bill->bill_number,
        ],
    ]);
});

it('filters bills by status', function () {
    $user = User::factory()->citizen()->create();
    Bill::factory()->count(2)->openForParticipation()->create();
    Bill::factory()->count(3)->closed()->create();

    $response = actingAs($user)->getJson('/api/v1/bills?status=open_for_participation');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('filters bills by house', function () {
    $user = User::factory()->citizen()->create();
    Bill::factory()->create(['house' => 'senate']);
    Bill::factory()->create(['house' => 'senate']);
    Bill::factory()->create(['house' => 'national_assembly']);

    $response = actingAs($user)->getJson('/api/v1/bills?house=senate');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('requires authentication to access bills', function () {
    $response = $this->getJson('/api/v1/bills');

    $response->assertUnauthorized();
});

it('increments views count when bill is viewed', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create(['views_count' => 100]);

    actingAs($user)->getJson("/api/v1/bills/{$bill->id}");

    expect($bill->fresh()->views_count)->toBeGreaterThan(100);
});

it('returns 404 for non-existent bill', function () {
    $user = User::factory()->citizen()->create();

    $response = actingAs($user)->getJson('/api/v1/bills/999999');

    $response->assertNotFound();
});
