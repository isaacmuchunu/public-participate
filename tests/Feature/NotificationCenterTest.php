<?php

use App\Jobs\SendBillParticipationOpenedNotifications;
use App\Jobs\SendBillPublishedNotifications;
use App\Jobs\SendLegislatorFollowUpNotification;
use App\Jobs\SendSubmissionAggregatedNotification;
use App\Models\Bill;
use App\Models\CitizenEngagement;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\Bill\NewBillPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renders the notifications index with results', function () {
    $user = User::factory()->create(['role' => 'citizen']);

    DatabaseNotification::query()->create([
        'id' => Str::uuid()->toString(),
        'notifiable_id' => $user->id,
        'notifiable_type' => User::class,
        'type' => NewBillPublishedNotification::class,
        'data' => [
            'type' => 'bill_published',
            'title' => 'Universal Health Bill',
            'bill_id' => 101,
            'bill_number' => 'BILL-2025-ABC',
        ],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DatabaseNotification::query()->create([
        'id' => Str::uuid()->toString(),
        'notifiable_id' => $user->id,
        'notifiable_type' => User::class,
        'type' => NewBillPublishedNotification::class,
        'data' => [
            'type' => 'bill_participation_opened',
            'title' => 'Climate Action Bill',
            'bill_id' => 202,
            'bill_number' => 'BILL-2025-XYZ',
        ],
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 2)
            ->where('notifications.data.0.type', 'bill_published'));
});

it('marks a single notification as read', function () {
    $user = User::factory()->create(['role' => 'citizen']);

    $notification = DatabaseNotification::query()->create([
        'id' => Str::uuid()->toString(),
        'notifiable_id' => $user->id,
        'notifiable_type' => User::class,
        'type' => NewBillPublishedNotification::class,
        'data' => ['type' => 'bill_published'],
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAs($user)
        ->post(route('notifications.read', $notification), $this->withCsrf())
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not()->toBeNull();
});

it('dispatches bill published notifications when a bill is created', function () {
    Queue::fake();

    Bill::factory()->create([
        'status' => 'gazetted',
    ]);

    Queue::assertPushed(SendBillPublishedNotifications::class);
});

it('dispatches participation opened notifications when bill status changes', function () {
    Queue::fake();

    $bill = Bill::factory()->create([
        'status' => 'draft',
        'participation_start_date' => now()->addDay(),
        'participation_end_date' => now()->addDays(5),
    ]);

    Queue::fake();

    $bill->update(['status' => 'open_for_participation']);

    Queue::assertPushed(SendBillParticipationOpenedNotifications::class);
});

it('dispatches submission aggregated notification when status updated', function () {
    Queue::fake();

    $bill = Bill::factory()->create(['status' => 'open_for_participation']);
    $submission = Submission::factory()
        ->for($bill)
        ->create([
            'status' => 'pending',
        ]);

    Queue::fake();

    $submission->update(['status' => 'included']);

    Queue::assertPushed(SendSubmissionAggregatedNotification::class);
});

it('allows legislators to send citizen engagements and dispatch follow-up job', function () {
    Queue::fake();

    $legislator = User::factory()->create([
        'role' => 'senator',
        'legislative_house' => 'senate',
    ]);

    $citizen = User::factory()->create(['role' => 'citizen']);
    $bill = Bill::factory()->create();
    $submission = Submission::factory()
        ->for($bill)
        ->for($citizen)
        ->create([
            'status' => 'pending',
        ]);

    actingAs($legislator)
        ->post(route('submissions.engagements.store', $submission), $this->withCsrf([
            'subject' => 'Clarify county programmes',
            'message' => 'Could you expand on the implementation details you referenced?',
        ]))
        ->assertRedirect();

    expect(CitizenEngagement::count())->toBe(1);

    Queue::assertPushed(SendLegislatorFollowUpNotification::class);
});

it('prevents citizens from sending follow-ups', function () {
    $citizen = User::factory()->create(['role' => 'citizen']);
    $other = User::factory()->create(['role' => 'citizen']);
    $bill = Bill::factory()->create();
    $submission = Submission::factory()
        ->for($bill)
        ->for($other)
        ->create();

    actingAs($citizen)
        ->post(route('submissions.engagements.store', $submission), $this->withCsrf([
            'subject' => 'Follow up',
            'message' => 'Test message',
        ]))
        ->assertForbidden();
});
