<?php

declare(strict_types=1);

use App\Data\KnowledgeBase\CreateArticleData;
use App\Data\KnowledgeBase\UpdateArticleData;
use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBContentFormat;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBTag;
use App\Models\Platform\SuperAdminUser;
use App\Services\KnowledgeBase\KBArticleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(KBArticleService::class);
    $this->admin = SuperAdminUser::factory()->active()->create();
    $this->category = KBCategory::factory()->public()->create();
});

describe('listPublished', function () {
    it('returns only published articles', function () {
        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->count(2)
            ->create();

        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $result = $this->service->listPublished();

        expect($result->total())->toBe(2);
    });

    it('filters by category', function () {
        $otherCategory = KBCategory::factory()->create();

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        KBArticle::factory()
            ->published()
            ->for($otherCategory, 'category')
            ->create();

        $result = $this->service->listPublished(['category_id' => $this->category->id]);

        expect($result->total())->toBe(1);
    });

    it('filters by article type', function () {
        KBArticle::factory()
            ->published()
            ->howTo()
            ->for($this->category, 'category')
            ->create();

        KBArticle::factory()
            ->published()
            ->faq()
            ->for($this->category, 'category')
            ->create();

        $result = $this->service->listPublished(['article_type' => 'how_to']);

        expect($result->total())->toBe(1);
    });
});

describe('getBySlug', function () {
    it('returns published article by slug', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['slug' => 'test-article']);

        $result = $this->service->getBySlug('test-article');

        expect($result->id)->toBe($article->id);
    });

    it('throws exception for non-existent slug', function () {
        expect(fn () => $this->service->getBySlug('non-existent'))
            ->toThrow(ModelNotFoundException::class);
    });

    it('throws exception for draft article slug', function () {
        KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create(['slug' => 'draft-article']);

        expect(fn () => $this->service->getBySlug('draft-article'))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('getFeatured', function () {
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

        $result = $this->service->getFeatured();

        expect($result)->toHaveCount(3);
    });

    it('respects limit parameter', function () {
        KBArticle::factory()
            ->published()
            ->featured()
            ->for($this->category, 'category')
            ->count(10)
            ->create();

        $result = $this->service->getFeatured(5);

        expect($result)->toHaveCount(5);
    });
});

describe('getPopular', function () {
    it('returns articles ordered by view count', function () {
        $popular = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['view_count' => 100]);

        KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['view_count' => 10]);

        $result = $this->service->getPopular(10);

        expect($result->first()->id)->toBe($popular->id);
    });
});

describe('create', function () {
    it('creates a new article', function () {
        $data = new CreateArticleData(
            category_id: $this->category->id,
            title: 'Test Article',
            content: 'This is the content',
        );

        $article = $this->service->create($this->admin, $data);

        expect($article->title)->toBe('Test Article');
        expect($article->status)->toBe(KBArticleStatus::DRAFT);
        expect($article->author_id)->toBe($this->admin->id);
    });

    it('generates slug from title', function () {
        $data = new CreateArticleData(
            category_id: $this->category->id,
            title: 'How To Create Posts',
            content: 'Content',
        );

        $article = $this->service->create($this->admin, $data);

        expect($article->slug)->toBe('how-to-create-posts');
    });

    it('creates unique slug when duplicate exists', function () {
        KBArticle::factory()
            ->for($this->category, 'category')
            ->create(['slug' => 'test']);

        $data = new CreateArticleData(
            category_id: $this->category->id,
            title: 'Test',
            content: 'Content',
        );

        $article = $this->service->create($this->admin, $data);

        expect($article->slug)->toBe('test-1');
    });

    it('throws exception for invalid category', function () {
        $data = new CreateArticleData(
            category_id: '00000000-0000-0000-0000-000000000000',
            title: 'Test',
            content: 'Content',
        );

        expect(fn () => $this->service->create($this->admin, $data))
            ->toThrow(ValidationException::class);
    });

    it('syncs tags on creation', function () {
        $tags = KBTag::factory()->count(2)->create();

        $data = new CreateArticleData(
            category_id: $this->category->id,
            title: 'Test',
            content: 'Content',
            tag_ids: $tags->pluck('id')->toArray(),
        );

        $article = $this->service->create($this->admin, $data);

        expect($article->tags)->toHaveCount(2);
    });
});

describe('update', function () {
    it('updates article fields', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->byAuthor($this->admin)
            ->create(['title' => 'Original']);

        $data = new UpdateArticleData(title: 'Updated');

        $result = $this->service->update($article, $this->admin, $data);

        expect($result->title)->toBe('Updated');
    });

    it('increments version on update', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->byAuthor($this->admin)
            ->create(['version' => 1]);

        $data = new UpdateArticleData(title: 'New Title');

        $result = $this->service->update($article, $this->admin, $data);

        expect($result->version)->toBe(2);
    });

    it('creates version snapshot', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->byAuthor($this->admin)
            ->create();

        $data = new UpdateArticleData(title: 'New Title');

        $this->service->update($article, $this->admin, $data);

        expect($article->versions()->count())->toBe(1);
    });

    it('updates last_edited_by', function () {
        $originalAuthor = SuperAdminUser::factory()->create();
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->byAuthor($originalAuthor)
            ->create();

        $data = new UpdateArticleData(title: 'New Title');

        $result = $this->service->update($article, $this->admin, $data);

        expect($result->last_edited_by)->toBe($this->admin->id);
    });
});

describe('publish', function () {
    it('publishes a draft article', function () {
        $article = KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $result = $this->service->publish($article);

        expect($result->status)->toBe(KBArticleStatus::PUBLISHED);
        expect($result->published_at)->not->toBeNull();
    });

    it('throws exception when publishing already published article', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        expect(fn () => $this->service->publish($article))
            ->toThrow(ValidationException::class);
    });

    it('allows publishing archived article', function () {
        $article = KBArticle::factory()
            ->archived()
            ->for($this->category, 'category')
            ->create();

        $result = $this->service->publish($article);

        expect($result->status)->toBe(KBArticleStatus::PUBLISHED);
    });

    it('increments category article count', function () {
        $article = KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $this->service->publish($article);

        $this->category->refresh();
        expect($this->category->article_count)->toBe(1);
    });
});

describe('unpublish', function () {
    it('sets published article to draft', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $this->category->update(['article_count' => 1]);

        $result = $this->service->unpublish($article);

        expect($result->status)->toBe(KBArticleStatus::DRAFT);
    });

    it('decrements category article count', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $this->category->update(['article_count' => 1]);

        $this->service->unpublish($article);

        $this->category->refresh();
        expect($this->category->article_count)->toBe(0);
    });
});

describe('archive', function () {
    it('archives a published article', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $result = $this->service->archive($article);

        expect($result->status)->toBe(KBArticleStatus::ARCHIVED);
    });
});

describe('delete', function () {
    it('deletes an article', function () {
        $article = KBArticle::factory()
            ->for($this->category, 'category')
            ->create();

        $articleId = $article->id;

        $this->service->delete($article);

        expect(KBArticle::find($articleId))->toBeNull();
    });

    it('decrements category count when deleting published article', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        $this->category->update(['article_count' => 1]);

        $this->service->delete($article);

        $this->category->refresh();
        expect($this->category->article_count)->toBe(0);
    });
});

describe('incrementViewCount', function () {
    it('increments article view count', function () {
        $article = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create(['view_count' => 10]);

        $this->service->incrementViewCount($article);

        $article->refresh();
        expect($article->view_count)->toBe(11);
    });
});
