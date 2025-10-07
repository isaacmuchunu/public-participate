<?php

use App\Models\Bill;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('creates submission with all required fields', function () {
    $user = User::factory()->citizen()->create();
    $bill = Bill::factory()->openForParticipation()->create();

    $submission = Submission::factory()->create([
        'user_id' => $user->id,
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my detailed submission content that is at least fifty characters long.',
        'language' => 'en',
    ]);

    expect($submission->user_id)->toBe($user->id);
    expect($submission->bill_id)->toBe($bill->id);
    expect($submission->submission_type)->toBe('support');
    expect($submission->content)->not->toBeNull();
    expect($submission->language)->toBe('en');
    expect($submission->status)->not->toBeNull();
});

it('generates unique tracking id on creation', function () {
    $submission1 = Submission::factory()->create();
    $submission2 = Submission::factory()->create();

    expect($submission1->tracking_id)->not->toBe($submission2->tracking_id);
    expect($submission1->tracking_id)->toHaveLength(12);
    expect($submission2->tracking_id)->toHaveLength(12);
});

it('creates submission in pending status by default', function () {
    $submission = Submission::factory()->pending()->create();

    expect($submission->status)->toBe('pending');
    expect($submission->review_notes)->toBeNull();
    expect($submission->reviewed_at)->toBeNull();
    expect($submission->reviewed_by)->toBeNull();
});

it('associates submission with bill', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $submission = Submission::factory()->create(['bill_id' => $bill->id]);

    expect($submission->bill->id)->toBe($bill->id);
});

it('associates submission with user', function () {
    $user = User::factory()->citizen()->create();
    $submission = Submission::factory()->create(['user_id' => $user->id]);

    expect($submission->user->id)->toBe($user->id);
});

it('stores metadata as array', function () {
    $metadata = [
        'sentiment' => 'positive',
        'keywords' => ['governance', 'transparency'],
    ];
    $submission = Submission::factory()->create(['metadata' => $metadata]);

    expect($submission->metadata)->toBeArray();
    expect($submission->metadata['sentiment'])->toBe('positive');
});

it('supports all submission types', function () {
    $types = ['support', 'oppose', 'amend', 'neutral'];

    foreach ($types as $type) {
        $submission = Submission::factory()->create(['submission_type' => $type]);
        expect($submission->submission_type)->toBe($type);
    }
});

it('supports all languages', function () {
    $languages = ['en', 'sw', 'other'];

    foreach ($languages as $language) {
        $submission = Submission::factory()->create(['language' => $language]);
        expect($submission->language)->toBe($language);
    }
});

it('filters submissions by type', function () {
    Submission::factory()->create(['submission_type' => 'support']);
    Submission::factory()->create(['submission_type' => 'support']);
    Submission::factory()->create(['submission_type' => 'oppose']);

    $supportSubmissions = Submission::byType('support')->get();

    expect($supportSubmissions)->toHaveCount(2);
});

it('filters submissions by status', function () {
    Submission::factory()->pending()->create();
    Submission::factory()->pending()->create();
    Submission::factory()->approved()->create();

    $pendingSubmissions = Submission::byStatus('pending')->get();

    expect($pendingSubmissions)->toHaveCount(2);
});

it('filters submissions by county', function () {
    Submission::factory()->create(['submitter_county' => 'Nairobi']);
    Submission::factory()->create(['submitter_county' => 'Nairobi']);
    Submission::factory()->create(['submitter_county' => 'Mombasa']);

    $nairobiSubmissions = Submission::byCounty('Nairobi')->get();

    expect($nairobiSubmissions)->toHaveCount(2);
});

it('can create submission without user (anonymous)', function () {
    $bill = Bill::factory()->openForParticipation()->create();

    $submission = Submission::factory()->create([
        'bill_id' => $bill->id,
        'user_id' => null,
        'submitter_name' => 'Anonymous Citizen',
        'submitter_phone' => '0712345678',
        'submitter_email' => 'citizen@example.com',
    ]);

    expect($submission->user_id)->toBeNull();
    expect($submission->submitter_name)->toBe('Anonymous Citizen');
});

it('stores channel information', function () {
    $submission = Submission::factory()->create(['channel' => 'web']);

    expect($submission->channel)->toBe('web');
});

it('can be associated with clause', function () {
    $submission = Submission::factory()->create();

    expect($submission->clause())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

it('has engagements relationship', function () {
    $submission = Submission::factory()->create();

    expect($submission->engagements())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
