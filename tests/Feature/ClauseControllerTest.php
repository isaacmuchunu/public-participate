<?php

use App\Models\Bill;
use App\Models\BillClause;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

it('lists all clauses for a bill', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create(['status' => 'open_for_participation']);

    $clause1 = BillClause::factory()->section()->create([
        'bill_id' => $bill->id,
        'clause_number' => '1',
        'display_order' => 0,
    ]);

    $clause2 = BillClause::factory()->section()->create([
        'bill_id' => $bill->id,
        'clause_number' => '2',
        'display_order' => 1,
    ]);

    $response = $this->actingAs($user)->getJson("/api/v1/bills/{$bill->id}/clauses");

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.clause_number', '1')
        ->assertJsonPath('data.1.clause_number', '2');
});

it('shows a specific clause with details', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create();
    $clause = BillClause::factory()->create([
        'bill_id' => $bill->id,
        'clause_number' => '5',
        'title' => 'Implementation',
        'content' => 'This Act shall come into force...',
    ]);

    $response = $this->actingAs($user)->getJson("/api/v1/bills/{$bill->id}/clauses/{$clause->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.clause_number', '5')
        ->assertJsonPath('data.title', 'Implementation')
        ->assertJsonPath('data.content', 'This Act shall come into force...');
});

it('returns 404 when clause does not belong to bill', function () {
    $user = User::factory()->create();
    $bill1 = Bill::factory()->create();
    $bill2 = Bill::factory()->create();
    $clause = BillClause::factory()->create(['bill_id' => $bill2->id]);

    $response = $this->actingAs($user)->getJson("/api/v1/bills/{$bill1->id}/clauses/{$clause->id}");

    $response->assertNotFound();
});

it('allows clerk to create a new clause', function () {
    $clerk = User::factory()->create(['role' => 'clerk']);
    $bill = Bill::factory()->create();

    $response = $this->actingAs($clerk)
        ->postJson("/api/v1/bills/{$bill->id}/clauses", [
            'clause_number' => '10',
            'clause_type' => 'section',
            'title' => 'Penalties',
            'content' => 'Any person who contravenes...',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.clause_number', '10')
        ->assertJsonPath('data.title', 'Penalties');

    $this->assertDatabaseHas('bill_clauses', [
        'bill_id' => $bill->id,
        'clause_number' => '10',
        'title' => 'Penalties',
    ]);
});

it('prevents non-clerk from creating clauses', function () {
    $citizen = User::factory()->create(['role' => 'citizen']);
    $bill = Bill::factory()->create();

    $response = $this->actingAs($citizen)
        ->postJson("/api/v1/bills/{$bill->id}/clauses", [
            'clause_number' => '10',
            'clause_type' => 'section',
            'title' => 'Penalties',
            'content' => 'Content here',
        ]);

    $response->assertForbidden();
});

it('validates required fields when creating clause', function () {
    $clerk = User::factory()->create(['role' => 'clerk']);
    $bill = Bill::factory()->create();

    $response = $this->actingAs($clerk)
        ->postJson("/api/v1/bills/{$bill->id}/clauses", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['clause_number', 'clause_type', 'content']);
});

it('allows clerk to update a clause', function () {
    $clerk = User::factory()->create(['role' => 'clerk']);
    $bill = Bill::factory()->create();
    $clause = BillClause::factory()->create([
        'bill_id' => $bill->id,
        'title' => 'Original Title',
    ]);

    $response = $this->actingAs($clerk)
        ->patchJson("/api/v1/bills/{$bill->id}/clauses/{$clause->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('bill_clauses', [
        'id' => $clause->id,
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ]);
});

it('allows clerk to delete a clause', function () {
    $clerk = User::factory()->create(['role' => 'clerk']);
    $bill = Bill::factory()->create();
    $clause = BillClause::factory()->create(['bill_id' => $bill->id]);

    $response = $this->actingAs($clerk)
        ->deleteJson("/api/v1/bills/{$bill->id}/clauses/{$clause->id}");

    $response->assertSuccessful();

    $this->assertDatabaseMissing('bill_clauses', [
        'id' => $clause->id,
    ]);
});

it('prevents deleting clause from wrong bill', function () {
    $clerk = User::factory()->create(['role' => 'clerk']);
    $bill1 = Bill::factory()->create();
    $bill2 = Bill::factory()->create();
    $clause = BillClause::factory()->create(['bill_id' => $bill2->id]);

    $response = $this->actingAs($clerk)
        ->deleteJson("/api/v1/bills/{$bill1->id}/clauses/{$clause->id}");

    $response->assertNotFound();

    $this->assertDatabaseHas('bill_clauses', [
        'id' => $clause->id,
    ]);
});

it('loads clause relationships when showing details', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create();
    $parent = BillClause::factory()->section()->create(['bill_id' => $bill->id]);
    $child = BillClause::factory()->subsection()->create([
        'bill_id' => $bill->id,
        'parent_clause_id' => $parent->id,
    ]);

    $response = $this->actingAs($user)->getJson("/api/v1/bills/{$bill->id}/clauses/{$parent->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'clause_number',
                'title',
                'content',
                'children',
                'parent',
                'analytics',
                'submissions',
            ],
        ]);
});

it('counts submissions for each clause', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create(['status' => 'open_for_participation']);
    $clause1 = BillClause::factory()->create(['bill_id' => $bill->id]);
    $clause2 = BillClause::factory()->create(['bill_id' => $bill->id]);

    // Create submissions for clause1
    \App\Models\Submission::factory()->count(3)->create([
        'bill_id' => $bill->id,
        'clause_id' => $clause1->id,
        'submission_scope' => 'clause',
    ]);

    $response = $this->actingAs($user)->getJson("/api/v1/bills/{$bill->id}/clauses");

    $response->assertSuccessful()
        ->assertJsonPath('data.0.submissions_count', 3)
        ->assertJsonPath('data.1.submissions_count', 0);
});
