<?php

declare(strict_types=1);

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBSearchAnalytic;

beforeEach(function () {
    $this->category = KBCategory::factory()->public()->create();
});

describe('GET /api/v1/kb/search', function () {
    it('searches articles by title', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'How to create a post']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Getting started guide']);

        $response = $this->getJson('/api/v1/kb/search?q=post');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'category_name',
                        'article_type',
                        'relevance_score',
                    ],
                ],
                'meta' => [
                    'query',
                    'total',
                ],
            ])
            ->assertJsonCount(1, 'data');
    });

    it('searches articles by content', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create([
                'title' => 'Introduction',
                'content' => 'This article explains how to schedule posts.',
            ]);

        $response = $this->getJson('/api/v1/kb/search?q=schedule');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('returns 422 for query shorter than 2 characters', function () {
        $response = $this->getJson('/api/v1/kb/search?q=a');

        $response->assertStatus(422);
    });

    it('logs search queries', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Test article']);

        $this->getJson('/api/v1/kb/search?q=test');

        expect(KBSearchAnalytic::where('search_query', 'test')->exists())->toBeTrue();
    });

    it('filters search results by category', function () {
        $otherCategory = KBCategory::factory()->public()->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Test article one']);

        KBArticle::factory()
            ->published()
            ->for($otherCategory, 'category')
            ->create(['title' => 'Test article two']);

        $response = $this->getJson("/api/v1/kb/search?q=test&category_id={$this->category->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('does not return draft articles', function () {
        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create(['title' => 'Draft test article']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Published test article']);

        $response = $this->getJson('/api/v1/kb/search?q=test');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/kb/search/suggest', function () {
    it('returns article suggestions', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'How to create posts']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'How to schedule posts']);

        $response = $this->getJson('/api/v1/kb/search/suggest?q=how');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'slug'],
                ],
            ])
            ->assertJsonCount(2, 'data');
    });

    it('returns empty for short queries', function () {
        $response = $this->getJson('/api/v1/kb/search/suggest?q=a');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('respects limit parameter', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(10)
            ->create(['title' => 'How to do something']);

        $response = $this->getJson('/api/v1/kb/search/suggest?q=how&limit=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });
});

describe('GET /api/v1/kb/search/popular', function () {
    it('returns popular search queries', function () {
        KBSearchAnalytic::factory()
            ->successful()
            ->create(['search_query_normalized' => 'how to post']);

        KBSearchAnalytic::factory()
            ->successful()
            ->count(5)
            ->create(['search_query_normalized' => 'schedule']);

        $response = $this->getJson('/api/v1/kb/search/popular');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['query', 'count'],
                ],
            ]);

        $data = $response->json('data');
        // Schedule should be first since it has more searches
        expect($data[0]['query'])->toBe('schedule');
    });

    it('excludes searches with no results', function () {
        KBSearchAnalytic::factory()
            ->create([
                'search_query_normalized' => 'no results query',
                'results_count' => 0,
            ]);

        KBSearchAnalytic::factory()
            ->successful()
            ->create(['search_query_normalized' => 'has results']);

        $response = $this->getJson('/api/v1/kb/search/popular');

        $response->assertOk();
        $queries = collect($response->json('data'))->pluck('query')->toArray();
        expect($queries)->not->toContain('no results query');
    });
});
