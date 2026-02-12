<?php

declare(strict_types=1);

/**
 * KBTag Model Unit Tests
 *
 * Tests for the KBTag model which represents tags for knowledge base articles.
 *
 * @see \App\Models\KnowledgeBase\KBTag
 */

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBTag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create tag with factory', function (): void {
    $tag = KBTag::factory()->create();

    expect($tag)->toBeInstanceOf(KBTag::class)
        ->and($tag->id)->not->toBeNull()
        ->and($tag->name)->not->toBeNull()
        ->and($tag->slug)->not->toBeNull();
});

test('has correct table name', function (): void {
    $tag = new KBTag();

    expect($tag->getTable())->toBe('kb_tags');
});

test('casts attributes correctly', function (): void {
    $tag = KBTag::factory()->create(['usage_count' => 10]);

    expect($tag->usage_count)->toBeInt();
});

test('articles relationship works', function (): void {
    $tag = KBTag::factory()->create();
    $articles = KBArticle::factory()->count(3)->create();

    foreach ($articles as $article) {
        $article->tags()->attach($tag->id);
    }

    expect($tag->articles)->toHaveCount(3)
        ->and($tag->articles->first())->toBeInstanceOf(KBArticle::class);
});

test('popular scope orders by usage count descending', function (): void {
    KBTag::factory()->create(['usage_count' => 10]);
    KBTag::factory()->create(['usage_count' => 50]);
    KBTag::factory()->create(['usage_count' => 25]);

    $popular = KBTag::popular()->get();

    expect($popular->first()->usage_count)->toBe(50)
        ->and($popular->last()->usage_count)->toBe(10);
});

test('ordered scope orders alphabetically by name', function (): void {
    KBTag::factory()->create(['name' => 'Zebra']);
    KBTag::factory()->create(['name' => 'Alpha']);
    KBTag::factory()->create(['name' => 'Middle']);

    $ordered = KBTag::ordered()->get();

    expect($ordered->first()->name)->toBe('Alpha')
        ->and($ordered->last()->name)->toBe('Zebra');
});

test('search scope filters by name or slug', function (): void {
    KBTag::factory()->create(['name' => 'Testing', 'slug' => 'testing']);
    KBTag::factory()->create(['name' => 'Other', 'slug' => 'other']);
    KBTag::factory()->create(['name' => 'Test Related', 'slug' => 'test-related']);

    $results = KBTag::search('test')->get();

    expect($results)->toHaveCount(2);
});

test('incrementUsageCount increases count', function (): void {
    $tag = KBTag::factory()->create(['usage_count' => 5]);

    $tag->incrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(6);
});

test('decrementUsageCount decreases count', function (): void {
    $tag = KBTag::factory()->create(['usage_count' => 5]);

    $tag->decrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(4);
});

test('decrementUsageCount does not go below zero', function (): void {
    $tag = KBTag::factory()->create(['usage_count' => 0]);

    $tag->decrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(0);
});

test('recalculateUsageCount counts actual article relationships', function (): void {
    $tag = KBTag::factory()->create(['usage_count' => 100]);
    $articles = KBArticle::factory()->count(3)->create();

    foreach ($articles as $article) {
        $article->tags()->attach($tag->id);
    }

    $tag->recalculateUsageCount();

    expect($tag->usage_count)->toBe(3);
});

test('popular factory state sets high usage count', function (): void {
    $tag = KBTag::factory()->popular()->create();

    expect($tag->usage_count)->toBeGreaterThanOrEqual(50);
});

test('withUsageCount factory state sets specific count', function (): void {
    $tag = KBTag::factory()->withUsageCount(42)->create();

    expect($tag->usage_count)->toBe(42);
});
