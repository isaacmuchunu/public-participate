<?php

use App\Models\BillClause;
use App\Models\ClauseAnalytics;

it('can create clause analytics', function () {
    $clause = BillClause::factory()->create();

    $analytics = ClauseAnalytics::factory()->create([
        'clause_id' => $clause->id,
        'submissions_count' => 100,
        'support_count' => 60,
        'oppose_count' => 30,
        'neutral_count' => 10,
    ]);

    expect($analytics)->toBeInstanceOf(ClauseAnalytics::class)
        ->and($analytics->clause_id)->toBe($clause->id)
        ->and($analytics->submissions_count)->toBe(100)
        ->and($analytics->support_count)->toBe(60)
        ->and($analytics->oppose_count)->toBe(30)
        ->and($analytics->neutral_count)->toBe(10);
});

it('belongs to a clause', function () {
    $clause = BillClause::factory()->create();
    $analytics = ClauseAnalytics::factory()->create(['clause_id' => $clause->id]);

    expect($analytics->clause)->toBeInstanceOf(BillClause::class)
        ->and($analytics->clause->id)->toBe($clause->id);
});

it('calculates support percentage correctly', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'submissions_count' => 100,
        'support_count' => 75,
        'oppose_count' => 20,
        'neutral_count' => 5,
    ]);

    expect($analytics->getSupportPercentage())->toBe(75.0);
});

it('calculates oppose percentage correctly', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'submissions_count' => 100,
        'support_count' => 30,
        'oppose_count' => 60,
        'neutral_count' => 10,
    ]);

    expect($analytics->getOpposePercentage())->toBe(60.0);
});

it('calculates neutral percentage correctly', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'submissions_count' => 100,
        'support_count' => 40,
        'oppose_count' => 35,
        'neutral_count' => 25,
    ]);

    expect($analytics->getNeutralPercentage())->toBe(25.0);
});

it('returns zero percentages when no submissions', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'submissions_count' => 0,
        'support_count' => 0,
        'oppose_count' => 0,
        'neutral_count' => 0,
    ]);

    expect($analytics->getSupportPercentage())->toBe(0.0)
        ->and($analytics->getOpposePercentage())->toBe(0.0)
        ->and($analytics->getNeutralPercentage())->toBe(0.0);
});

it('identifies support as dominant sentiment', function () {
    $analytics = ClauseAnalytics::factory()->highSupport()->create();

    expect($analytics->getDominantSentiment())->toBe('support');
});

it('identifies opposition as dominant sentiment', function () {
    $analytics = ClauseAnalytics::factory()->highOpposition()->create();

    expect($analytics->getDominantSentiment())->toBe('oppose');
});

it('identifies neutral as dominant sentiment', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'support_count' => 10,
        'oppose_count' => 15,
        'neutral_count' => 75,
    ]);

    expect($analytics->getDominantSentiment())->toBe('neutral');
});

it('handles tied sentiment counts', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'support_count' => 33,
        'oppose_count' => 33,
        'neutral_count' => 34,
    ]);

    expect($analytics->getDominantSentiment())->toBe('neutral');
});

it('casts sentiment_scores to array', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'sentiment_scores' => ['positive' => 0.75, 'negative' => 0.15, 'neutral' => 0.10],
    ]);

    expect($analytics->sentiment_scores)->toBeArray()
        ->and($analytics->sentiment_scores['positive'])->toBe(0.75);
});

it('casts top_keywords to array', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'top_keywords' => ['education', 'funding', 'implementation'],
    ]);

    expect($analytics->top_keywords)->toBeArray()
        ->toHaveCount(3)
        ->toContain('education');
});

it('casts last_analyzed_at to datetime', function () {
    $now = now();
    $analytics = ClauseAnalytics::factory()->create([
        'last_analyzed_at' => $now,
    ]);

    expect($analytics->last_analyzed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($analytics->last_analyzed_at->timestamp)->toBe($now->timestamp);
});

it('rounds percentages to two decimal places', function () {
    $analytics = ClauseAnalytics::factory()->create([
        'submissions_count' => 3,
        'support_count' => 1,
        'oppose_count' => 1,
        'neutral_count' => 1,
    ]);

    expect($analytics->getSupportPercentage())->toBe(33.33)
        ->and($analytics->getOpposePercentage())->toBe(33.33)
        ->and($analytics->getNeutralPercentage())->toBe(33.33);
});
