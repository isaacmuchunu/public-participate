<?php

use App\Http\Requests\Bill\StoreBillRequest;
use Illuminate\Support\Facades\Validator;

it('requires title field', function () {
    $data = [
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

it('requires description field', function () {
    $data = [
        'title' => 'Test Bill',
        'type' => 'public',
        'house' => 'senate',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('description'))->toBeTrue();
});

it('requires type field', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'house' => 'senate',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('type'))->toBeTrue();
});

it('requires house field', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('house'))->toBeTrue();
});

it('validates type enum values', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'invalid_type',
        'house' => 'senate',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('type'))->toBeTrue();
});

it('validates house enum values', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'invalid_house',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('house'))->toBeTrue();
});

it('accepts valid type values', function () {
    $validTypes = ['public', 'private', 'money'];

    foreach ($validTypes as $type) {
        $data = [
            'title' => 'Test Bill',
            'description' => 'Bill description',
            'type' => $type,
            'house' => 'senate',
        ];

        $validator = Validator::make($data, (new StoreBillRequest)->rules());
        expect($validator->passes())->toBeTrue();
    }
});

it('accepts valid house values', function () {
    $validHouses = ['national_assembly', 'senate', 'both'];

    foreach ($validHouses as $house) {
        $data = [
            'title' => 'Test Bill',
            'description' => 'Bill description',
            'type' => 'public',
            'house' => $house,
        ];

        $validator = Validator::make($data, (new StoreBillRequest)->rules());
        expect($validator->passes())->toBeTrue();
    }
});

it('validates participation_end_date is after participation_start_date', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'participation_start_date' => '2025-02-01',
        'participation_end_date' => '2025-01-01',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('participation_end_date'))->toBeTrue();
});

it('accepts valid participation dates', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'participation_start_date' => '2025-01-01',
        'participation_end_date' => '2025-02-01',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->passes())->toBeTrue();
});

it('accepts optional fields', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'sponsor' => 'Senator John Doe',
        'committee' => 'Finance Committee',
        'gazette_date' => '2025-01-01',
        'tags' => ['governance', 'health'],
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->passes())->toBeTrue();
});

it('validates tags as array', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'tags' => 'invalid_string',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags'))->toBeTrue();
});

it('validates individual tag strings', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'tags' => ['governance', 123],
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.1'))->toBeTrue();
});

it('enforces maximum tag length', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'tags' => [str_repeat('a', 51)],
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

it('enforces title max length', function () {
    $data = [
        'title' => str_repeat('a', 256),
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

it('validates gazette_date as date', function () {
    $data = [
        'title' => 'Test Bill',
        'description' => 'Bill description',
        'type' => 'public',
        'house' => 'senate',
        'gazette_date' => 'invalid-date',
    ];

    $validator = Validator::make($data, (new StoreBillRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('gazette_date'))->toBeTrue();
});
