<?php

declare(strict_types=1);

/**
 * KBSearchAnalytic Model Unit Tests
 *
 * Tests for the KBSearchAnalytic model which represents search analytics.
 *
 * @see \App\Models\KnowledgeBase\KBSearchAnalytic
 */

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBSearchAnalytic;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create search analytic with factory', function (): void {
    $analytic = KBSearchAnalytic::factory()->create();

    expect($analytic)->toBeInstanceOf(KBSearchAnalytic::class)
        ->and($analytic->id)->not->toBeNull()
        ->and($analytic->search_query)->not->toBeNull();
});

test('has correct table name', function (): void {
    $analytic = new KBSearchAnalytic();

    expect($analytic->getTable())->toBe('kb_search_analytics');
});

test('casts attributes correctly', function (): void {
    $analytic = KBSearchAnalytic::factory()->create([
        'results_count' => 10,
        'search_successful' => true,
    ]);

    expect($analytic->results_count)->toBeInt()
        ->and($analytic->search_successful)->toBeBool();
});

test('clickedArticle relationship works', function (): void {
    $article = KBArticle::factory()->create();
    $analytic = KBSearchAnalytic::factory()->withClick($article)->create();

    expect($analytic->clickedArticle)->toBeInstanceOf(KBArticle::class)
        ->and($analytic->clickedArticle->id)->toBe($article->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $analytic = KBSearchAnalytic::factory()->forUser($user)->create();

    expect($analytic->user)->toBeInstanceOf(User::class)
        ->and($analytic->user->id)->toBe($user->id);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $analytic = KBSearchAnalytic::factory()->forTenant($tenant)->create();

    expect($analytic->tenant)->toBeInstanceOf(Tenant::class)
        ->and($analytic->tenant->id)->toBe($tenant->id);
});

test('successful scope filters successful searches', function (): void {
    KBSearchAnalytic::factory()->successful()->count(2)->create();
    KBSearchAnalytic::factory()->noResults()->create();

    expect(KBSearchAnalytic::successful()->count())->toBe(2);
});

test('noResults scope filters zero result searches', function (): void {
    KBSearchAnalytic::factory()->successful()->create();
    KBSearchAnalytic::factory()->noResults()->count(2)->create();

    expect(KBSearchAnalytic::noResults()->count())->toBe(2);
});

test('inDateRange scope filters by date range', function (): void {
    KBSearchAnalytic::factory()->create(['created_at' => now()->subDays(5)]);
    KBSearchAnalytic::factory()->create(['created_at' => now()->subDays(3)]);
    KBSearchAnalytic::factory()->create(['created_at' => now()->subDays(10)]);

    $start = now()->subDays(7);
    $end = now();

    expect(KBSearchAnalytic::inDateRange($start, $end)->count())->toBe(2);
});

test('forUser scope filters by user', function (): void {
    $user = User::factory()->create();
    KBSearchAnalytic::factory()->forUser($user)->count(2)->create();
    KBSearchAnalytic::factory()->create();

    expect(KBSearchAnalytic::forUser($user->id)->count())->toBe(2);
});

test('markAsSuccessful updates search_successful', function (): void {
    $analytic = KBSearchAnalytic::factory()->create(['search_successful' => null]);

    $analytic->markAsSuccessful();

    expect($analytic->fresh()->search_successful)->toBeTrue();
});

test('recordClick sets article and marks successful', function (): void {
    $article = KBArticle::factory()->create();
    $analytic = KBSearchAnalytic::factory()->create([
        'clicked_article_id' => null,
        'search_successful' => null,
    ]);

    $analytic->recordClick($article->id);

    expect($analytic->fresh()->clicked_article_id)->toBe($article->id)
        ->and($analytic->fresh()->search_successful)->toBeTrue();
});

test('successful factory state creates successful search', function (): void {
    $analytic = KBSearchAnalytic::factory()->successful()->create();

    expect($analytic->search_successful)->toBeTrue()
        ->and($analytic->results_count)->toBeGreaterThan(0);
});

test('noResults factory state creates zero result search', function (): void {
    $analytic = KBSearchAnalytic::factory()->noResults()->create();

    expect($analytic->results_count)->toBe(0)
        ->and($analytic->search_successful)->toBeFalse();
});

test('withQuery factory state sets specific query', function (): void {
    $analytic = KBSearchAnalytic::factory()
        ->withQuery('How to connect Facebook')
        ->create();

    expect($analytic->search_query)->toBe('How to connect Facebook')
        ->and($analytic->search_query_normalized)->toBe('how to connect facebook');
});

test('withClick factory state includes article reference', function (): void {
    $article = KBArticle::factory()->create();
    $analytic = KBSearchAnalytic::factory()->withClick($article)->create();

    expect($analytic->clicked_article_id)->toBe($article->id)
        ->and($analytic->search_successful)->toBeTrue()
        ->and($analytic->results_count)->toBeGreaterThan(0);
});
