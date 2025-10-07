<?php

use App\Models\Bill;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('correctly identifies bill open for participation', function () {
    $bill = Bill::factory()->openForParticipation()->create();

    expect($bill->isOpenForParticipation())->toBeTrue();
});

it('identifies draft bill not open for participation', function () {
    $bill = Bill::factory()->draft()->create();

    expect($bill->isOpenForParticipation())->toBeFalse();
});

it('identifies closed bill not open for participation', function () {
    $bill = Bill::factory()->closed()->create();

    expect($bill->isOpenForParticipation())->toBeFalse();
});

it('identifies bill with future start date not open for participation', function () {
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->addDays(5),
        'participation_end_date' => now()->addDays(35),
    ]);

    expect($bill->isOpenForParticipation())->toBeFalse();
});

it('identifies bill with past end date not open for participation', function () {
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(35),
        'participation_end_date' => now()->subDays(5),
    ]);

    expect($bill->isOpenForParticipation())->toBeFalse();
});

it('calculates days remaining correctly for open bill', function () {
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(5),
        'participation_end_date' => now()->addDays(25),
    ]);

    $daysRemaining = $bill->daysRemaining();

    expect($daysRemaining)->toBeGreaterThan(0);
    expect($daysRemaining)->toBeLessThanOrEqual(25);
});

it('returns 0 days remaining when bill is closed', function () {
    $bill = Bill::factory()->closed()->create();

    expect($bill->daysRemaining())->toBe(0);
});

it('returns 0 days remaining when participation ended', function () {
    $bill = Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(35),
        'participation_end_date' => now()->subDays(5),
    ]);

    expect($bill->daysRemaining())->toBe(0);
});

it('filters open bills with scope', function () {
    Bill::factory()->openForParticipation()->create();
    Bill::factory()->openForParticipation()->create();
    Bill::factory()->draft()->create();
    Bill::factory()->closed()->create();

    $openBills = Bill::openForParticipation()->get();

    expect($openBills)->toHaveCount(2);
    $openBills->each(function ($bill) {
        expect($bill->status)->toBe('open_for_participation');
        expect($bill->isOpenForParticipation())->toBeTrue();
    });
});

it('excludes bills with future start date from scope', function () {
    Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->addDays(5),
        'participation_end_date' => now()->addDays(35),
    ]);

    $openBills = Bill::openForParticipation()->get();

    expect($openBills)->toHaveCount(0);
});

it('excludes bills with past end date from scope', function () {
    Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(35),
        'participation_end_date' => now()->subDays(5),
    ]);

    $openBills = Bill::openForParticipation()->get();

    expect($openBills)->toHaveCount(0);
});

it('includes bill starting today in scope', function () {
    Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now(),
        'participation_end_date' => now()->addDays(30),
    ]);

    $openBills = Bill::openForParticipation()->get();

    expect($openBills)->toHaveCount(1);
});

it('includes bill ending today in scope', function () {
    Bill::factory()->create([
        'status' => 'open_for_participation',
        'participation_start_date' => now()->subDays(30),
        'participation_end_date' => now(),
    ]);

    $openBills = Bill::openForParticipation()->get();

    expect($openBills)->toHaveCount(1);
});
