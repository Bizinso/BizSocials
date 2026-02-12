<?php

declare(strict_types=1);

use App\Data\KnowledgeBase\CreateCategoryData;
use App\Data\KnowledgeBase\UpdateCategoryData;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Services\KnowledgeBase\KBCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(KBCategoryService::class);
});

describe('listWithArticleCount', function () {
    it('returns only public categories', function () {
        KBCategory::factory()->public()->count(2)->create();
        KBCategory::factory()->create(['is_public' => false]);

        $result = $this->service->listWithArticleCount();

        expect($result)->toHaveCount(2);
    });

    it('returns categories ordered by sort_order', function () {
        $cat3 = KBCategory::factory()->public()->create(['sort_order' => 3]);
        $cat1 = KBCategory::factory()->public()->create(['sort_order' => 1]);
        $cat2 = KBCategory::factory()->public()->create(['sort_order' => 2]);

        $result = $this->service->listWithArticleCount();

        expect($result->first()->id)->toBe($cat1->id);
    });
});

describe('getBySlug', function () {
    it('returns public category by slug', function () {
        $category = KBCategory::factory()->public()->create(['slug' => 'test-category']);

        $result = $this->service->getBySlug('test-category');

        expect($result->id)->toBe($category->id);
    });

    it('throws exception for non-existent slug', function () {
        expect(fn () => $this->service->getBySlug('non-existent'))
            ->toThrow(ModelNotFoundException::class);
    });

    it('throws exception for non-public category', function () {
        KBCategory::factory()->create([
            'slug' => 'private',
            'is_public' => false,
        ]);

        expect(fn () => $this->service->getBySlug('private'))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('getTree', function () {
    it('returns hierarchical category structure', function () {
        $parent = KBCategory::factory()->public()->create();
        KBCategory::factory()->public()->childOf($parent)->count(2)->create();

        $result = $this->service->getTree();

        $parentNode = $result->first();
        expect($parentNode->children)->toHaveCount(2);
    });
});

describe('create', function () {
    it('creates a new category', function () {
        $data = new CreateCategoryData(name: 'New Category');

        $result = $this->service->create($data);

        expect($result->name)->toBe('New Category');
        expect($result->is_public)->toBeTrue();
    });

    it('generates slug from name', function () {
        $data = new CreateCategoryData(name: 'Getting Started');

        $result = $this->service->create($data);

        expect($result->slug)->toBe('getting-started');
    });

    it('creates unique slug when duplicate exists', function () {
        KBCategory::factory()->create(['slug' => 'test']);

        $data = new CreateCategoryData(name: 'Test');

        $result = $this->service->create($data);

        expect($result->slug)->toBe('test-1');
    });

    it('creates child category', function () {
        $parent = KBCategory::factory()->create();

        $data = new CreateCategoryData(
            name: 'Child',
            parent_id: $parent->id,
        );

        $result = $this->service->create($data);

        expect($result->parent_id)->toBe($parent->id);
    });

    it('throws exception for invalid parent', function () {
        $data = new CreateCategoryData(
            name: 'Child',
            parent_id: '00000000-0000-0000-0000-000000000000',
        );

        expect(fn () => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });

    it('sets sort_order automatically', function () {
        KBCategory::factory()->create(['sort_order' => 5]);

        $data = new CreateCategoryData(name: 'New');

        $result = $this->service->create($data);

        expect($result->sort_order)->toBe(6);
    });
});

describe('update', function () {
    it('updates category fields', function () {
        $category = KBCategory::factory()->create(['name' => 'Original']);

        $data = new UpdateCategoryData(name: 'Updated');

        $result = $this->service->update($category, $data);

        expect($result->name)->toBe('Updated');
    });

    it('updates category slug', function () {
        $category = KBCategory::factory()->create(['slug' => 'original']);

        $data = new UpdateCategoryData(slug: 'new-slug');

        $result = $this->service->update($category, $data);

        expect($result->slug)->toBe('new-slug');
    });

    it('creates unique slug if updating to existing slug', function () {
        KBCategory::factory()->create(['slug' => 'existing']);
        $category = KBCategory::factory()->create(['slug' => 'original']);

        $data = new UpdateCategoryData(slug: 'existing');

        $result = $this->service->update($category, $data);

        expect($result->slug)->toBe('existing-1');
    });
});

describe('updateOrder', function () {
    it('updates sort order for multiple categories', function () {
        $cat1 = KBCategory::factory()->create(['sort_order' => 1]);
        $cat2 = KBCategory::factory()->create(['sort_order' => 2]);

        $this->service->updateOrder([
            ['id' => $cat1->id, 'sort_order' => 2],
            ['id' => $cat2->id, 'sort_order' => 1],
        ]);

        $cat1->refresh();
        $cat2->refresh();

        expect($cat1->sort_order)->toBe(2);
        expect($cat2->sort_order)->toBe(1);
    });
});

describe('delete', function () {
    it('deletes an empty category', function () {
        $category = KBCategory::factory()->create();
        $categoryId = $category->id;

        $this->service->delete($category);

        expect(KBCategory::find($categoryId))->toBeNull();
    });

    it('throws exception when deleting category with articles', function () {
        $category = KBCategory::factory()->create(['article_count' => 1]);

        KBArticle::factory()->for($category, 'category')->create();

        expect(fn () => $this->service->delete($category))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when deleting category with children', function () {
        $parent = KBCategory::factory()->create();
        KBCategory::factory()->childOf($parent)->create();

        expect(fn () => $this->service->delete($parent))
            ->toThrow(ValidationException::class);
    });
});
