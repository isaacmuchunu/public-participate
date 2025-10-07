<?php

use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('creates submission under review', function () {
    $submission = Submission::factory()->underReview()->create();

    expect($submission->status)->toBe('under_review');
    expect($submission->reviewed_at)->toBeNull();
    expect($submission->reviewed_by)->toBeNull();
});

it('creates approved submission with review details', function () {
    $reviewer = User::factory()->clerk()->create();
    $submission = Submission::factory()->approved()->create([
        'reviewed_by' => $reviewer->id,
    ]);

    expect($submission->status)->toBe('approved');
    expect($submission->review_notes)->not->toBeNull();
    expect($submission->reviewed_at)->not->toBeNull();
    expect($submission->reviewed_by)->toBe($reviewer->id);
});

it('creates rejected submission with review details', function () {
    $reviewer = User::factory()->clerk()->create();
    $submission = Submission::factory()->rejected()->create([
        'reviewed_by' => $reviewer->id,
    ]);

    expect($submission->status)->toBe('rejected');
    expect($submission->review_notes)->not->toBeNull();
    expect($submission->reviewed_at)->not->toBeNull();
    expect($submission->reviewed_by)->toBe($reviewer->id);
});

it('creates included submission', function () {
    $submission = Submission::factory()->included()->create();

    expect($submission->status)->toBe('included');
    expect($submission->reviewed_at)->not->toBeNull();
});

it('associates submission with reviewer', function () {
    $reviewer = User::factory()->clerk()->create();
    $submission = Submission::factory()->approved()->create([
        'reviewed_by' => $reviewer->id,
    ]);

    expect($submission->reviewer->id)->toBe($reviewer->id);
    expect($submission->reviewer->role->value)->toBe('clerk');
});

it('updates submission from pending to under review', function () {
    $submission = Submission::factory()->pending()->create();

    $submission->update(['status' => 'under_review']);

    expect($submission->fresh()->status)->toBe('under_review');
});

it('updates submission from under review to approved', function () {
    $reviewer = User::factory()->clerk()->create();
    $submission = Submission::factory()->underReview()->create();

    $submission->update([
        'status' => 'approved',
        'review_notes' => 'This submission is well-reasoned and valuable.',
        'reviewed_at' => now(),
        'reviewed_by' => $reviewer->id,
    ]);

    $fresh = $submission->fresh();
    expect($fresh->status)->toBe('approved');
    expect($fresh->review_notes)->not->toBeNull();
    expect($fresh->reviewed_at)->not->toBeNull();
    expect($fresh->reviewed_by)->toBe($reviewer->id);
});

it('updates submission from under review to rejected', function () {
    $reviewer = User::factory()->clerk()->create();
    $submission = Submission::factory()->underReview()->create();

    $submission->update([
        'status' => 'rejected',
        'review_notes' => 'This submission does not meet the criteria.',
        'reviewed_at' => now(),
        'reviewed_by' => $reviewer->id,
    ]);

    $fresh = $submission->fresh();
    expect($fresh->status)->toBe('rejected');
    expect($fresh->review_notes)->not->toBeNull();
});

it('tracks review timestamp', function () {
    $beforeReview = now();

    $submission = Submission::factory()->pending()->create();

    $submission->update([
        'status' => 'approved',
        'reviewed_at' => now(),
    ]);

    $afterReview = now();

    expect($submission->fresh()->reviewed_at)
        ->toBeGreaterThanOrEqual($beforeReview)
        ->toBeLessThanOrEqual($afterReview);
});

it('can store detailed review notes', function () {
    $notes = 'This submission provides excellent insights into the governance implications. The analysis of Section 12 is particularly well-reasoned and supported by relevant case studies.';

    $submission = Submission::factory()->approved()->create([
        'review_notes' => $notes,
    ]);

    expect($submission->review_notes)->toBe($notes);
});

it('allows null review notes for pending submissions', function () {
    $submission = Submission::factory()->pending()->create([
        'review_notes' => null,
    ]);

    expect($submission->review_notes)->toBeNull();
});

it('casts reviewed_at as datetime', function () {
    $submission = Submission::factory()->approved()->create();

    expect($submission->reviewed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('tracks multiple status changes', function () {
    $submission = Submission::factory()->pending()->create();
    expect($submission->status)->toBe('pending');

    $submission->update(['status' => 'under_review']);
    expect($submission->fresh()->status)->toBe('under_review');

    $submission->update(['status' => 'approved', 'reviewed_at' => now()]);
    expect($submission->fresh()->status)->toBe('approved');
});

it('transitions from approved to included', function () {
    $submission = Submission::factory()->approved()->create();

    $submission->update(['status' => 'included']);

    expect($submission->fresh()->status)->toBe('included');
});
