<?php

declare(strict_types=1);

use App\Data\Support\CreateCategoryData;
use App\Data\Support\UpdateCategoryData;
use App\Models\Support\SupportCategory;
use App\Services\Support\SupportCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(SupportCategoryService::class);
});

describe('listActive', function () {
    it('returns only active categories', function () {
        SupportCategory::factory()->active()->count(3)->create();
        SupportCategory::factory()->inactive()->count(2)->create();

        $result = $this->service->listActive();

        expect($result)->toHaveCount(3);
    });

    it('returns categories ordered by sort_order', function () {
        SupportCategory::factory()->active()->create(['sort_order' => 20]);
        SupportCategory::factory()->active()->create(['sort_order' => 10]);
        SupportCategory::factory()->active()->create(['sort_order' => 30]);

        $result = $this->service->listActive();

        expect($result[0]->sort_order)->toBe(10);
        expect($result[1]->sort_order)->toBe(20);
        expect($result[2]->sort_order)->toBe(30);
    });
});

describe('list', function () {
    it('returns all categories including inactive', function () {
        SupportCategory::factory()->active()->count(3)->create();
        SupportCategory::factory()->inactive()->count(2)->create();

        $result = $this->service->list();

        expect($result)->toHaveCount(5);
    });
});

describe('get', function () {
    it('returns category by id', function () {
        $category = SupportCategory::factory()->create();

        $result = $this->service->get($category->id);

        expect($result->id)->toBe($category->id);
    });

    it('throws exception for non-existent category', function () {
        expect(fn () => $this->service->get('00000000-0000-0000-0000-000000000000'))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('create', function () {
    it('creates a new category', function () {
        $data = new CreateCategoryData(
            name: 'Billing Issues',
            description: 'Issues related to billing',
            color: '#FF5733',
            icon: 'credit-card',
            sort_order: 10,
        );

        $category = $this->service->create($data);

        expect($category->name)->toBe('Billing Issues');
        expect($category->slug)->toBe('billing-issues');
        expect($category->color)->toBe('#FF5733');
        expect($category->is_active)->toBeTrue();
    });

    it('generates unique slug', function () {
        SupportCategory::factory()->create(['slug' => 'test-category']);

        $data = new CreateCategoryData(name: 'Test Category');

        $category = $this->service->create($data);

        expect($category->slug)->toBe('test-category-1');
    });

    it('creates child category with parent', function () {
        $parent = SupportCategory::factory()->create();

        $data = new CreateCategoryData(
            name: 'Child Category',
            parent_id: $parent->id,
        );

        $category = $this->service->create($data);

        expect($category->parent_id)->toBe($parent->id);
    });

    it('throws exception for invalid parent', function () {
        $data = new CreateCategoryData(
            name: 'Test',
            parent_id: '00000000-0000-0000-0000-000000000000',
        );

        expect(fn () => $this->service->create($data))
            ->toThrow(ValidationException::class);
    });
});

describe('update', function () {
    it('updates category fields', function () {
        $category = SupportCategory::factory()->create([
            'name' => 'Original Name',
        ]);

        $data = new UpdateCategoryData(
            name: 'Updated Name',
            description: 'New description',
        );

        $result = $this->service->update($category, $data);

        expect($result->name)->toBe('Updated Name');
        expect($result->description)->toBe('New description');
    });

    it('updates slug when name changes', function () {
        $category = SupportCategory::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $data = new UpdateCategoryData(name: 'New Name');

        $result = $this->service->update($category, $data);

        expect($result->slug)->toBe('new-name');
    });

    it('throws exception when setting itself as parent', function () {
        $category = SupportCategory::factory()->create();

        $data = new UpdateCategoryData(parent_id: $category->id);

        expect(fn () => $this->service->update($category, $data))
            ->toThrow(ValidationException::class);
    });
});

describe('delete', function () {
    it('deletes category without tickets', function () {
        $category = SupportCategory::factory()->create(['ticket_count' => 0]);

        $categoryId = $category->id;

        $this->service->delete($category);

        expect(SupportCategory::find($categoryId))->toBeNull();
    });

    it('throws exception for category with tickets', function () {
        $category = SupportCategory::factory()->withTicketCount(5)->create();

        expect(fn () => $this->service->delete($category))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for category with children', function () {
        $parent = SupportCategory::factory()->create();
        SupportCategory::factory()->child($parent)->create();

        expect(fn () => $this->service->delete($parent))
            ->toThrow(ValidationException::class);
    });
});
