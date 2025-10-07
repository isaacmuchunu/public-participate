<?php

use App\Models\Bill;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('generates unique bill number on creation', function () {

    $bill1 = Bill::factory()->create();
    $bill2 = Bill::factory()->create();

    expect($bill1->bill_number)->not->toBe($bill2->bill_number);
    expect($bill1->bill_number)->toStartWith('BILL-'.now()->year);
});

it('creates bill in draft status', function () {
    $bill = Bill::factory()->draft()->create();

    expect($bill->status)->toBe('draft');
    expect($bill->participation_start_date)->toBeNull();
    expect($bill->participation_end_date)->toBeNull();
});

it('creates bill in gazetted status', function () {
    $bill = Bill::factory()->gazetted()->create();

    expect($bill->status)->toBe('gazetted');
    expect($bill->gazette_date)->not->toBeNull();
});

it('creates bill open for participation', function () {
    $bill = Bill::factory()->openForParticipation()->create();

    expect($bill->status)->toBe('open_for_participation');
    expect($bill->participation_start_date)->not->toBeNull();
    expect($bill->participation_end_date)->not->toBeNull();
    expect($bill->participation_end_date->greaterThan($bill->participation_start_date))->toBeTrue();
});

it('creates bill in closed status', function () {
    $bill = Bill::factory()->closed()->create();

    expect($bill->status)->toBe('closed');
    expect($bill->participation_end_date)->lessThan(now());
});

it('validates participation dates logic', function () {
    $bill = Bill::factory()->create([
        'participation_start_date' => now()->addDays(5),
        'participation_end_date' => now()->addDays(10),
    ]);

    expect($bill->participation_end_date->greaterThan($bill->participation_start_date))->toBeTrue();
});

it('associates bill with creator', function () {
    $user = User::factory()->clerk()->create();
    $bill = Bill::factory()->create(['created_by' => $user->id]);

    expect($bill->creator->id)->toBe($user->id);
    expect($bill->creator->role->value)->toBe('clerk');
});

it('stores tags as array', function () {
    $tags = ['governance', 'health', 'education'];
    $bill = Bill::factory()->create(['tags' => $tags]);

    expect($bill->tags)->toBeArray();
    expect($bill->tags)->toBe($tags);
});

it('casts dates correctly', function () {
    $bill = Bill::factory()->create([
        'gazette_date' => '2025-01-15',
        'participation_start_date' => '2025-01-20',
        'participation_end_date' => '2025-02-20',
    ]);

    expect($bill->gazette_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($bill->participation_start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($bill->participation_end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('has submissions relationship', function () {
    $bill = Bill::factory()->openForParticipation()->create();

    expect($bill->submissions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('has clauses relationship', function () {
    $bill = Bill::factory()->create();

    expect($bill->clauses())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($bill->topLevelClauses())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('has summary relationship', function () {
    $bill = Bill::factory()->create();

    expect($bill->summary())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
});

it('filters bills by tag', function () {
    Bill::factory()->create(['tags' => ['governance', 'health']]);
    Bill::factory()->create(['tags' => ['education', 'economy']]);
    Bill::factory()->create(['tags' => ['governance', 'economy']]);

    $governanceBills = Bill::byTag('governance')->get();
    $educationBills = Bill::byTag('education')->get();

    expect($governanceBills)->toHaveCount(2);
    expect($educationBills)->toHaveCount(1);
});

it('increments views count', function () {
    $bill = Bill::factory()->create(['views_count' => 100]);

    $bill->increment('views_count');
    $bill->refresh();

    expect($bill->views_count)->toBe(101);
});

it('increments submissions count', function () {
    $bill = Bill::factory()->create(['submissions_count' => 50]);

    $bill->increment('submissions_count');
    $bill->refresh();

    expect($bill->submissions_count)->toBe(51);
});
