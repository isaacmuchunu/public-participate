<?php

use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Models\Bill;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    Notification::fake();
});

it('requires bill_id field', function () {
    $data = [
        'submission_type' => 'support',
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('bill_id'))->toBeTrue();
});

it('requires submission_type field', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('submission_type'))->toBeTrue();
});

it('requires content field', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

it('requires language field', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my submission content that is long enough to pass validation.',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('language'))->toBeTrue();
});

it('enforces minimum content length', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'Short',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

it('enforces maximum content length', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => str_repeat('a', 10001),
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

it('validates submission_type enum values', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'invalid_type',
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('submission_type'))->toBeTrue();
});

it('validates language enum values', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'invalid_language',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('language'))->toBeTrue();
});

it('accepts valid submission_type values', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $validTypes = ['support', 'oppose', 'amend', 'neutral'];

    foreach ($validTypes as $type) {
        $data = [
            'bill_id' => $bill->id,
            'submission_type' => $type,
            'content' => 'This is my submission content that is long enough to pass validation.',
            'language' => 'en',
        ];

        $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());
        expect($validator->passes())->toBeTrue();
    }
});

it('accepts valid language values', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $validLanguages = ['en', 'sw', 'other'];

    foreach ($validLanguages as $language) {
        $data = [
            'bill_id' => $bill->id,
            'submission_type' => 'support',
            'content' => 'This is my submission content that is long enough to pass validation.',
            'language' => $language,
        ];

        $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());
        expect($validator->passes())->toBeTrue();
    }
});

it('validates bill exists', function () {
    $data = [
        'bill_id' => 999999,
        'submission_type' => 'support',
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('bill_id'))->toBeTrue();
});

it('accepts optional submitter fields', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'en',
        'submitter_name' => 'John Doe',
        'submitter_phone' => '0712345678',
        'submitter_email' => 'john@example.com',
        'submitter_county' => 'Nairobi',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->passes())->toBeTrue();
});

it('validates submitter_email format', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => 'This is my submission content that is long enough to pass validation.',
        'language' => 'en',
        'submitter_email' => 'invalid-email',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('submitter_email'))->toBeTrue();
});

it('accepts content with valid HTML tags', function () {
    $bill = Bill::factory()->openForParticipation()->create();
    $data = [
        'bill_id' => $bill->id,
        'submission_type' => 'support',
        'content' => '<p>This is <strong>bold</strong> and <em>italic</em> text with a <br> line break.</p>',
        'language' => 'en',
    ];

    $validator = Validator::make($data, (new StoreSubmissionRequest)->rules());

    expect($validator->passes())->toBeTrue();
});
