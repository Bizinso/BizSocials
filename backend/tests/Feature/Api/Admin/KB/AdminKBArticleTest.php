<?php

declare(strict_types=1);

use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBTag;
use App\Models\Platform\SuperAdminUser;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->superAdmin()->create();
    $this->category = KBCategory::factory()->public()->create();

    Sanctum::actingAs($this->admin, ['*'], 'sanctum');
});

describe('GET /api/v1/admin/kb/articles', function () {
    it('lists all articles including drafts', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $response = $this->getJson('/api/v1/admin/kb/articles');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'slug', 'article_type'],
                ],
                'meta',
            ])
            ->assertJsonCount(2, 'data');
    });

    it('filters articles by status', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(2)
            ->create();

        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $response = $this->getJson('/api/v1/admin/kb/articles?status=draft');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('searches articles by title', function () {
        KBArticle::factory()
            ->for($this->category, 'category')
            ->create(['title' => 'How to create posts']);

        KBArticle::factory()
            ->for($this->category, 'category')
            ->create(['title' => 'Getting started guide']);

        $response = $this->getJson('/api/v1/admin/kb/articles?search=posts');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/admin/kb/articles', function () {
    it('creates a new draft article', function () {
        $response = $this->postJson('/api/v1/admin/kb/articles', [
            'category_id' => $this->category->id,
            'title' => 'New Test Article',
            'content' => 'This is the article content.',
            'excerpt' => 'Short description',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'status',
                    'category_id',
                ],
            ])
            ->assertJson([
                'data' => [
                    'title' => 'New Test Article',
                    'status' => 'draft',
                ],
            ]);

        expect(KBArticle::where('title', 'New Test Article')->exists())->toBeTrue();
    });

    it('generates slug from title', function () {
        $response = $this->postJson('/api/v1/admin/kb/articles', [
            'category_id' => $this->category->id,
            'title' => 'How To Create Posts',
            'content' => 'Content here',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'slug' => 'how-to-create-posts',
                ],
            ]);
    });

    it('creates unique slug when duplicate exists', function () {
        KBArticle::factory()
            ->for($this->category, 'category')
            ->create(['slug' => 'test-article']);

        $response = $this->postJson('/api/v1/admin/kb/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'Content here',
        ]);

        $response->assertCreated();
        $slug = $response->json('data.slug');
        expect($slug)->toBe('test-article-1');
    });

    it('assigns tags to article', function () {
        $tags = KBTag::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/admin/kb/articles', [
            'category_id' => $this->category->id,
            'title' => 'Article With Tags',
            'content' => 'Content here',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.tags');
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/admin/kb/articles', [
            'excerpt' => 'Just an excerpt',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'title', 'content']);
    });

    it('validates category exists', function () {
        $response = $this->postJson('/api/v1/admin/kb/articles', [
            'category_id' => '00000000-0000-0000-0000-000000000000',
            'title' => 'Test Article',
            'content' => 'Content',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    });
});

describe('GET /api/v1/admin/kb/articles/{article}', function () {
    it('returns article details', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->create();

        $response = $this->getJson("/api/v1/admin/kb/articles/{$article->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'content_format',
                    'status',
                    'tags',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                ],
            ]);
    });

    it('returns 404 for non-existent article', function () {
        $response = $this->getJson('/api/v1/admin/kb/articles/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/admin/kb/articles/{article}', function () {
    it('updates article fields', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->create(['title' => 'Original Title']);

        $response = $this->putJson("/api/v1/admin/kb/articles/{$article->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                ],
            ]);

        $article->refresh();
        expect($article->title)->toBe('Updated Title');
        expect($article->version)->toBe(2);
    });

    it('creates version snapshot on update', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->create();

        $this->putJson("/api/v1/admin/kb/articles/{$article->id}", [
            'title' => 'New Title',
        ]);

        expect($article->versions()->count())->toBe(1);
    });

    it('updates article tags', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->create();

        $tags = KBTag::factory()->count(3)->create();

        $response = $this->putJson("/api/v1/admin/kb/articles/{$article->id}", [
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data.tags');
    });
});

describe('DELETE /api/v1/admin/kb/articles/{article}', function () {
    it('deletes an article', function () {
        $article = KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $response = $this->deleteJson("/api/v1/admin/kb/articles/{$article->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Article deleted successfully',
            ]);

        expect(KBArticle::find($article->id))->toBeNull();
    });
});

describe('POST /api/v1/admin/kb/articles/{article}/publish', function () {
    it('publishes a draft article', function () {
        $article = KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $response = $this->postJson("/api/v1/admin/kb/articles/{$article->id}/publish");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'published',
                ],
            ]);

        $article->refresh();
        expect($article->status)->toBe(KBArticleStatus::PUBLISHED);
        expect($article->published_at)->not->toBeNull();
    });

    it('increments category article count on publish', function () {
        $article = KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $this->postJson("/api/v1/admin/kb/articles/{$article->id}/publish");

        $this->category->refresh();
        expect($this->category->article_count)->toBe(1);
    });
});

describe('POST /api/v1/admin/kb/articles/{article}/unpublish', function () {
    it('unpublishes a published article', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        // Update category count
        $this->category->update(['article_count' => 1]);

        $response = $this->postJson("/api/v1/admin/kb/articles/{$article->id}/unpublish");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'draft',
                ],
            ]);
    });

    it('decrements category article count on unpublish', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $this->category->update(['article_count' => 1]);

        $this->postJson("/api/v1/admin/kb/articles/{$article->id}/unpublish");

        $this->category->refresh();
        expect($this->category->article_count)->toBe(0);
    });
});

describe('POST /api/v1/admin/kb/articles/{article}/archive', function () {
    it('archives an article', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $response = $this->postJson("/api/v1/admin/kb/articles/{$article->id}/archive");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'archived',
                ],
            ]);

        $article->refresh();
        expect($article->status)->toBe(KBArticleStatus::ARCHIVED);
    });
});
