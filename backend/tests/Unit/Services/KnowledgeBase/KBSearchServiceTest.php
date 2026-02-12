<?php

declare(strict_types=1);

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBSearchAnalytic;
use App\Services\KnowledgeBase\KBSearchService;

beforeEach(function () {
    $this->service = app(KBSearchService::class);
    $this->category = KBCategory::factory()->public()->create();
});

describe('search', function () {
    it('finds articles by title', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'How to create posts']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Getting started']);

        $result = $this->service->search('posts');

        expect($result->total())->toBe(1);
    });

    it('finds articles by content', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create([
                'title' => 'Introduction',
                'content' => 'This guide shows how to schedule content.',
            ]);

        $result = $this->service->search('schedule');

        expect($result->total())->toBe(1);
    });

    it('only returns published articles', function () {
        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create(['title' => 'Draft about testing']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Published about testing']);

        $result = $this->service->search('testing');

        expect($result->total())->toBe(1);
    });

    it('filters by category', function () {
        $otherCategory = KBCategory::factory()->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Test article one']);

        KBArticle::factory()
            ->published()
            ->for($otherCategory, 'category')
            ->create(['title' => 'Test article two']);

        $result = $this->service->search('test', ['category_id' => $this->category->id]);

        expect($result->total())->toBe(1);
    });

    it('is case insensitive', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'UPPERCASE TITLE']);

        $result = $this->service->search('uppercase');

        expect($result->total())->toBe(1);
    });
});

describe('suggest', function () {
    it('returns article suggestions matching query', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'How to create posts']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'How to schedule posts']);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Getting started']);

        $result = $this->service->suggest('how', 5);

        expect($result)->toHaveCount(2);
    });

    it('respects limit parameter', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(10)
            ->create(['title' => 'Test article']);

        $result = $this->service->suggest('test', 3);

        expect($result)->toHaveCount(3);
    });

    it('prioritizes title prefix matches', function () {
        $prefixMatch = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Testing best practices']);

        $containsMatch = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['title' => 'Best testing practices']);

        $result = $this->service->suggest('testing', 5);

        expect($result->first()->id)->toBe($prefixMatch->id);
    });
});

describe('logSearch', function () {
    it('creates search analytic record', function () {
        $this->service->logSearch('test query', 5, null);

        expect(KBSearchAnalytic::where('search_query', 'test query')->exists())->toBeTrue();
    });

    it('normalizes search query', function () {
        $this->service->logSearch('  TEST  Query  ', 5, null);

        $analytic = KBSearchAnalytic::first();
        expect($analytic->search_query_normalized)->toBe('test query');
    });

    it('records result count', function () {
        $this->service->logSearch('test', 10, null);

        $analytic = KBSearchAnalytic::first();
        expect($analytic->results_count)->toBe(10);
    });

    it('marks search as successful when results found', function () {
        $this->service->logSearch('test', 5, null);

        $analytic = KBSearchAnalytic::first();
        expect($analytic->search_successful)->toBeTrue();
    });

    it('marks search as unsuccessful when no results', function () {
        $this->service->logSearch('test', 0, null);

        $analytic = KBSearchAnalytic::first();
        expect($analytic->search_successful)->toBeFalse();
    });
});

describe('getPopularSearches', function () {
    it('returns search queries ordered by count', function () {
        KBSearchAnalytic::factory()
            ->successful()
            ->count(5)
            ->create(['search_query_normalized' => 'popular query']);

        KBSearchAnalytic::factory()
            ->successful()
            ->count(2)
            ->create(['search_query_normalized' => 'less popular']);

        $result = $this->service->getPopularSearches(10);

        expect($result->first()->query)->toBe('popular query');
    });

    it('excludes searches with no results', function () {
        KBSearchAnalytic::factory()
            ->successful()
            ->create(['search_query_normalized' => 'has results']);

        KBSearchAnalytic::factory()
            ->noResults()
            ->create(['search_query_normalized' => 'no results']);

        $result = $this->service->getPopularSearches(10);

        $queries = $result->pluck('query')->toArray();
        expect($queries)->not->toContain('no results');
        expect($queries)->toContain('has results');
    });

    it('respects limit parameter', function () {
        for ($i = 1; $i <= 20; $i++) {
            KBSearchAnalytic::factory()
                ->successful()
                ->create(['search_query_normalized' => "query {$i}"]);
        }

        $result = $this->service->getPopularSearches(5);

        expect($result)->toHaveCount(5);
    });
});
