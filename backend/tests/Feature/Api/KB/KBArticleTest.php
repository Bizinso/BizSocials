<?php

declare(strict_types=1);

use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBTag;

beforeEach(function () {
    $this->category = KBCategory::factory()->public()->create();
});

describe('GET /api/v1/kb/articles', function () {
    it('returns paginated published articles', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(3)
            ->create();

        // Create a draft article - should not appear
        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $response = $this->getJson('/api/v1/kb/articles');

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
                        'category_id',
                        'category_name',
                        'article_type',
                        'difficulty_level',
                        'view_count',
                        'is_featured',
                        'published_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters articles by category', function () {
        $otherCategory = KBCategory::factory()->public()->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(2)
            ->create();

        KBArticle::factory()
            ->published()
            ->for($otherCategory, 'category')
            ->create();

        $response = $this->getJson("/api/v1/kb/articles?category_id={$this->category->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters articles by article type', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->howTo()
            ->count(2)
            ->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->faq()
            ->create();

        $response = $this->getJson('/api/v1/kb/articles?article_type=how_to');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('GET /api/v1/kb/articles/featured', function () {
    it('returns featured articles', function () {
        KBArticle::factory()
            ->published()
            ->featured()
            ->for($this->category, 'category')
            ->count(3)
            ->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $response = $this->getJson('/api/v1/kb/articles/featured');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('respects limit parameter', function () {
        KBArticle::factory()
            ->published()
            ->featured()
            ->for($this->category, 'category')
            ->count(10)
            ->create();

        $response = $this->getJson('/api/v1/kb/articles/featured?limit=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });
});

describe('GET /api/v1/kb/articles/popular', function () {
    it('returns articles ordered by view count', function () {
        $popular = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['view_count' => 100]);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['view_count' => 10]);

        $response = $this->getJson('/api/v1/kb/articles/popular');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    ['id' => $popular->id],
                ],
            ]);
    });
});

describe('GET /api/v1/kb/articles/{slug}', function () {
    it('returns article by slug', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['slug' => 'test-article']);

        $response = $this->getJson('/api/v1/kb/articles/test-article');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'category_id',
                    'category_name',
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'content_format',
                    'article_type',
                    'difficulty_level',
                    'status',
                    'is_featured',
                    'view_count',
                    'helpful_count',
                    'not_helpful_count',
                    'helpfulness_score',
                    'tags',
                    'related_articles',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'slug' => 'test-article',
                ],
            ]);
    });

    it('returns 404 for non-existent slug', function () {
        $response = $this->getJson('/api/v1/kb/articles/non-existent');

        $response->assertNotFound();
    });

    it('returns 404 for draft articles', function () {
        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create(['slug' => 'draft-article']);

        $response = $this->getJson('/api/v1/kb/articles/draft-article');

        $response->assertNotFound();
    });

    it('increments view count on access', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['view_count' => 5]);

        $this->getJson("/api/v1/kb/articles/{$article->slug}");

        $article->refresh();
        expect($article->view_count)->toBe(6);
    });

    it('returns related articles', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(3)
            ->create();

        $response = $this->getJson("/api/v1/kb/articles/{$article->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'related_articles' => [
                        '*' => ['id', 'title', 'slug'],
                    ],
                ],
            ]);
    });
});
