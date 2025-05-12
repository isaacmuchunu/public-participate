<?php

use App\Models\Constituency;
use App\Models\County;
use App\Models\Ward;
use Database\Seeders\CountySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('imports counties, constituencies, and wards from the csv dataset', function () {
    $this->seed(CountySeeder::class);

    $county = County::query()->where('name', 'MOMBASA')->first();
    $constituency = Constituency::query()->where('name', 'CHANGAMWE')->first();
    $ward = Ward::query()->where('name', 'PORT REITZ')->first();

    expect($county)->not->toBeNull()
        ->and($county->code)->toBe('001');

    expect($constituency)
        ->not->toBeNull()
        ->and($constituency->county_id)->toBe($county?->id);

    expect($ward)
        ->not->toBeNull()
        ->and($ward->constituency_id)->toBe($constituency?->id);
});
