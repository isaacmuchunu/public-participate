<?php

use App\Models\Bill;
use App\Models\BillClause;
use App\Models\ClauseAnalytics;
use App\Models\Submission;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

it('can create a bill clause', function () {

    $bill = Bill::factory()->create();

    $clause = BillClause::factory()->create([
        'bill_id' => $bill->id,
        'clause_number' => '1',
        'clause_type' => 'section',
        'title' => 'Definitions',
        'content' => 'In this Act, unless the context otherwise requires...',
    ]);

    expect($clause)->toBeInstanceOf(BillClause::class)
        ->and($clause->bill_id)->toBe($bill->id)
        ->and($clause->clause_number)->toBe('1')
        ->and($clause->clause_type)->toBe('section')
        ->and($clause->title)->toBe('Definitions')
        ->and($clause->content)->toContain('In this Act');
});

it('belongs to a bill', function () {
    $bill = Bill::factory()->create();
    $clause = BillClause::factory()->create(['bill_id' => $bill->id]);

    expect($clause->bill)->toBeInstanceOf(Bill::class)
        ->and($clause->bill->id)->toBe($bill->id);
});

it('can have a parent clause', function () {
    $bill = Bill::factory()->create();
    $parentClause = BillClause::factory()->section()->create([
        'bill_id' => $bill->id,
        'clause_number' => '5',
    ]);

    $childClause = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parentClause->id,
        'clause_number' => '1',
    ]);

    expect($childClause->parent)->toBeInstanceOf(BillClause::class)
        ->and($childClause->parent->id)->toBe($parentClause->id)
        ->and($childClause->parent_clause_id)->toBe($parentClause->id);
});

it('can have multiple children clauses', function () {
    $bill = Bill::factory()->create();
    $parentClause = BillClause::factory()->section()->create(['bill_id' => $bill->id]);

    $child1 = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parentClause->id,
        'display_order' => 0,
    ]);

    $child2 = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parentClause->id,
        'display_order' => 1,
    ]);

    expect($parentClause->children)->toHaveCount(2)
        ->and($parentClause->children->first()->id)->toBe($child1->id)
        ->and($parentClause->children->last()->id)->toBe($child2->id);
});

it('can have submissions', function () {
    $clause = BillClause::factory()->create();

    $submission = Submission::factory()->create([
        'bill_id' => $clause->bill_id,
        'clause_id' => $clause->id,
        'submission_scope' => 'clause',
    ]);

    expect($clause->submissions)->toHaveCount(1)
        ->and($clause->submissions->first()->id)->toBe($submission->id);
});

it('can have analytics', function () {
    $clause = BillClause::factory()->create();

    $analytics = ClauseAnalytics::factory()->create([
        'clause_id' => $clause->id,
    ]);

    expect($clause->analytics)->toBeInstanceOf(ClauseAnalytics::class)
        ->and($clause->analytics->id)->toBe($analytics->id);
});

it('returns full clause number for top-level clause', function () {
    $clause = BillClause::factory()->section()->create([
        'clause_number' => '5',
    ]);

    expect($clause->getFullClauseNumber())->toBe('5');
});

it('returns full clause number for nested clause', function () {
    $bill = Bill::factory()->create();

    $section = BillClause::factory()->section()->create([
        'bill_id' => $bill->id,
        'clause_number' => '5',
    ]);

    $subsection = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $section->id,
        'clause_number' => '2',
    ]);

    $paragraph = BillClause::factory()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $subsection->id,
        'clause_number' => 'a',
        'clause_type' => 'paragraph',
    ]);

    expect($paragraph->getFullClauseNumber())->toBe('5.2.a');
});

it('returns clause path for nested clause', function () {
    $bill = Bill::factory()->create();

    $section = BillClause::factory()->section()->create([
        'bill_id' => $bill->id,
        'clause_number' => '3',
    ]);

    $subsection = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $section->id,
        'clause_number' => '1',
    ]);

    $path = $subsection->getClausePath();

    expect($path)->toBeArray()
        ->toHaveCount(2)
        ->and($path[0]->id)->toBe($section->id)
        ->and($path[1]->id)->toBe($subsection->id);
});

it('returns single-item clause path for top-level clause', function () {
    $clause = BillClause::factory()->section()->create();

    $path = $clause->getClausePath();

    expect($path)->toBeArray()
        ->toHaveCount(1)
        ->and($path[0]->id)->toBe($clause->id);
});

it('casts metadata to array', function () {
    $clause = BillClause::factory()->create([
        'metadata' => ['page_number' => 5, 'line_start' => 120],
    ]);

    expect($clause->metadata)->toBeArray()
        ->and($clause->metadata['page_number'])->toBe(5)
        ->and($clause->metadata['line_start'])->toBe(120);
});

it('orders children by display_order', function () {
    $bill = Bill::factory()->create();
    $parent = BillClause::factory()->section()->create(['bill_id' => $bill->id]);

    $child3 = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parent->id,
        'display_order' => 2,
    ]);

    $child1 = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parent->id,
        'display_order' => 0,
    ]);

    $child2 = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parent->id,
        'display_order' => 1,
    ]);

    $children = $parent->children;

    expect($children->first()->id)->toBe($child1->id)
        ->and($children->get(1)->id)->toBe($child2->id)
        ->and($children->last()->id)->toBe($child3->id);
});
