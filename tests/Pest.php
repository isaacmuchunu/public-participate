<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// Authentication helpers
function actingAsClerk(): Illuminate\Testing\TestResponse
{
    return test()->actingAs(App\Models\User::factory()->clerk()->create());
}

function actingAsLegislator(?string $house = 'senate'): Illuminate\Testing\TestResponse
{
    return test()->actingAs(App\Models\User::factory()->legislator($house)->create());
}

function actingAsCitizen(): Illuminate\Testing\TestResponse
{
    return test()->actingAs(App\Models\User::factory()->citizen()->create());
}

function actingAsAdmin(): Illuminate\Testing\TestResponse
{
    return test()->actingAs(App\Models\User::factory()->admin()->create());
}

// Data creation helpers
function createOpenBill(): App\Models\Bill
{
    return App\Models\Bill::factory()->openForParticipation()->create();
}

function createDraftBill(): App\Models\Bill
{
    return App\Models\Bill::factory()->draft()->create();
}

function createClosedBill(): App\Models\Bill
{
    return App\Models\Bill::factory()->closed()->create();
}

function createSubmission(?App\Models\Bill $bill = null, ?App\Models\User $user = null): App\Models\Submission
{
    return App\Models\Submission::factory()->create([
        'bill_id' => $bill?->id ?? App\Models\Bill::factory()->openForParticipation()->create()->id,
        'user_id' => $user?->id ?? App\Models\User::factory()->citizen()->create()->id,
    ]);
}

function createPendingSubmission(?App\Models\Bill $bill = null): App\Models\Submission
{
    return App\Models\Submission::factory()->pending()->create([
        'bill_id' => $bill?->id ?? App\Models\Bill::factory()->openForParticipation()->create()->id,
    ]);
}
