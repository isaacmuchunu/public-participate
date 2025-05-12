<?php

use App\Models\Bill;
use App\Models\Submission;
use App\Models\SubmissionDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withSession(['_token' => 'test-token']);
});

it('allows a citizen to create a submission draft', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDay(),
        'participation_end_date' => now()->addDays(10),
    ]);

    $response = $this->actingAs($user)->post(route('submissions.drafts.store'), [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'language' => 'en',
        'content' => 'I strongly support this bill and would like to share more thoughts later.',
        'contact_information' => [
            'name' => 'Jane Citizen',
            'email' => 'jane@example.com',
            'phone' => '0712345678',
            'county' => 'Nairobi',
        ],
        'attachments' => [
            [
                'id' => (string) Str::uuid(),
                'name' => 'supporting-document.pdf',
                'size' => 150000,
                'mime_type' => 'application/pdf',
            ],
        ],
        '_token' => 'test-token',
    ]);

    $draft = SubmissionDraft::query()->firstOrFail();

    $response->assertRedirect(
        route('submissions.create', ['bill_id' => $bill->id, 'draft_id' => $draft->id])
    );
    $response->assertSessionHas('success', 'Draft saved.');

    expect($draft)
        ->not->toBeNull();

    expect($draft->toArray())
        ->bill_id->toBe($bill->id)
        ->submission_type->toBe('support')
        ->language->toBe('en')
        ->contact_information->toMatchArray([
            'name' => 'Jane Citizen',
            'email' => 'jane@example.com',
            'phone' => '0712345678',
            'county' => 'Nairobi',
        ]);
});

it('allows a citizen to update their submission draft', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(2),
        'participation_end_date' => now()->addDays(5),
    ]);

    $draft = SubmissionDraft::factory()
        ->for($user)
        ->for($bill)
        ->create([
            'submission_type' => 'oppose',
            'language' => 'sw',
            'content' => 'Initial content',
        ]);

    expect($draft->user_id)->toBe($user->id);

    $response = $this->actingAs($user)->patch(route('submissions.drafts.update', $draft), [
        'submission_type' => 'amend',
        'language' => 'en',
        'content' => 'Updated content with more details for the clerk to review.',
        'attachments' => [],
        '_token' => 'test-token',
    ]);

    $response->assertRedirect(
        route('submissions.create', ['bill_id' => $bill->id, 'draft_id' => $draft->id])
    );
    $response->assertSessionHas('success', 'Draft updated.');

    $draft->refresh();

    expect($draft)
        ->submission_type->toBe('amend')
        ->language->toBe('en')
        ->content->toBe('Updated content with more details for the clerk to review.')
        ->attachments->toBe([])
        ->submitted_at->toBeNull();
});

it('allows a citizen to delete their submission draft', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(3),
        'participation_end_date' => now()->addDays(7),
    ]);

    $draft = SubmissionDraft::factory()
        ->for($user)
        ->for($bill)
        ->create();

    $response = $this->actingAs($user)->delete(route('submissions.drafts.destroy', $draft), [
        '_token' => 'test-token',
    ]);

    $response->assertRedirect(route('submissions.create'));
    $response->assertSessionHas('success', 'Draft removed.');

    expect(SubmissionDraft::query()->count())->toBe(0);
});

it('marks a draft as submitted when feedback is sent', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDay(),
        'participation_end_date' => now()->addDays(4),
    ]);

    $draft = SubmissionDraft::factory()
        ->for($user)
        ->for($bill)
        ->create([
            'submission_type' => 'support',
            'language' => 'en',
            'content' => 'Draft content that will be finalized soon.',
        ]);

    $response = $this->actingAs($user)->post(route('submissions.store'), [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'Final submission content that exceeds the minimum length.',
        'language' => 'en',
        'draft_id' => $draft->id,
        '_token' => 'test-token',
    ]);

    $submission = Submission::query()->firstOrFail();

    $response->assertRedirect(route('submissions.show', $submission));
    $response->assertSessionHas('success');

    expect($submission)
        ->not->toBeNull()
        ->bill_id->toBe($bill->id)
        ->user_id->toBe($user->id);

    expect($draft->fresh()->submitted_at)->not->toBeNull();
    expect($bill->fresh()->submissions()->count())->toBe(1);
});
