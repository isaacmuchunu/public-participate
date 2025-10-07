<?php

use App\Models\Bill;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('creates submission for open bill', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create();

    $response = actingAs($user)->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long for validation purposes.',
        'language' => 'en',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'data' => ['id', 'tracking_id', 'status', 'created_at'],
    ]);

    expect(Submission::count())->toBe(1);
});

it('prevents submission to closed bill', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->closed()->create();

    $response = actingAs($user)->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long for validation purposes.',
        'language' => 'en',
    ]);

    $response->assertUnprocessable();
    expect(Submission::count())->toBe(0);
});

it('prevents submission to draft bill', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->draft()->create();

    $response = actingAs($user)->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long for validation purposes.',
        'language' => 'en',
    ]);

    $response->assertUnprocessable();
});

it('validates required fields', function () {
    $user = User::factory()->citizen()->create();

    $response = actingAs($user)->postJson('/api/v1/submissions', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['bill_id', 'submission_type', 'content', 'language']);
});

it('validates minimum content length', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create();

    $response = actingAs($user)->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'Short',
        'language' => 'en',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['content']);
});

it('lists user submissions', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create();

    Submission::factory()->count(3)->create([
        'user_id' => $user->id,
        'bill_id' => $bill->id,
    ]);

    $response = actingAs($user)->getJson('/api/v1/submissions');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('shows submission details', function () {
    $user = User::factory()->citizen()->create();
    $submission = Submission::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/v1/submissions/{$submission->id}");

    $response->assertSuccessful();
    $response->assertJson([
        'data' => [
            'id' => $submission->id,
            'tracking_id' => $submission->tracking_id,
        ],
    ]);
});

it('prevents viewing others submissions', function () {
    $user1 = User::factory()->citizen()->create();
    $user2 = User::factory()->citizen()->create();
    $submission = Submission::factory()->create(['user_id' => $user2->id]);

    $response = actingAs($user1)->getJson("/api/v1/submissions/{$submission->id}");

    $response->assertForbidden();
});

it('requires authentication to create submission', function () {
    $bill = Bill::factory()->openForParticipation()->create();

    $response = $this->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long.',
        'language' => 'en',
    ]);

    $response->assertUnauthorized();
});

it('generates unique tracking id for submission', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create();

    $response = actingAs($user)->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long.',
        'language' => 'en',
    ]);

    $response->assertCreated();
    expect($response->json('data.tracking_id'))->not->toBeNull();
    expect($response->json('data.tracking_id'))->toHaveLength(12);
});

it('increments bill submissions count', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create(['submissions_count' => 50]);

    actingAs($user)->postJson('/api/v1/submissions', [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long.',
        'language' => 'en',
    ]);

    expect($bill->fresh()->submissions_count)->toBeGreaterThan(50);
});
