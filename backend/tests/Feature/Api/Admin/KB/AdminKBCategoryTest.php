<?php

declare(strict_types=1);

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\Platform\SuperAdminUser;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->superAdmin()->create();
    Sanctum::actingAs($this->admin, ['*'], 'sanctum');
});

describe('GET /api/v1/admin/kb/categories', function () {
    it('lists all categories including non-public', function () {
        KBCategory::factory()->public()->count(2)->create();
        KBCategory::factory()->create(['is_public' => false]);

        $response = $this->getJson('/api/v1/admin/kb/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'icon',
                        'sort_order',
                        'article_count',
                        'parent_id',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    });
});

describe('POST /api/v1/admin/kb/categories', function () {
    it('creates a new category', function () {
        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'name' => 'New Category',
            'description' => 'Category description',
            'icon' => 'folder',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'icon',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'New Category',
                    'slug' => 'new-category',
                ],
            ]);

        expect(KBCategory::where('name', 'New Category')->exists())->toBeTrue();
    });

    it('creates child category', function () {
        $parent = KBCategory::factory()->create();

        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'parent_id' => $parent->id,
                ],
            ]);
    });

    it('generates unique slug', function () {
        KBCategory::factory()->create(['slug' => 'test']);

        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'name' => 'Test',
        ]);

        $response->assertCreated();
        $slug = $response->json('data.slug');
        expect($slug)->toBe('test-1');
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'description' => 'Just description',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates parent category exists', function () {
        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'name' => 'Child',
            'parent_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('validates color format', function () {
        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'name' => 'Test',
            'color' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);
    });

    it('accepts valid hex color', function () {
        $response = $this->postJson('/api/v1/admin/kb/categories', [
            'name' => 'Test',
            'color' => '#FF5733',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'color' => '#FF5733',
                ],
            ]);
    });
});

describe('GET /api/v1/admin/kb/categories/{category}', function () {
    it('returns category details', function () {
        $category = KBCategory::factory()->create();

        $response = $this->getJson("/api/v1/admin/kb/categories/{$category->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
    });

    it('returns 404 for non-existent category', function () {
        $response = $this->getJson('/api/v1/admin/kb/categories/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/admin/kb/categories/{category}', function () {
    it('updates category fields', function () {
        $category = KBCategory::factory()->create(['name' => 'Original']);

        $response = $this->putJson("/api/v1/admin/kb/categories/{$category->id}", [
            'name' => 'Updated',
            'description' => 'New description',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Updated',
                    'description' => 'New description',
                ],
            ]);

        $category->refresh();
        expect($category->name)->toBe('Updated');
    });

    it('updates category slug', function () {
        $category = KBCategory::factory()->create(['slug' => 'original']);

        $response = $this->putJson("/api/v1/admin/kb/categories/{$category->id}", [
            'slug' => 'new-slug',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'slug' => 'new-slug',
                ],
            ]);
    });
});

describe('DELETE /api/v1/admin/kb/categories/{category}', function () {
    it('deletes an empty category', function () {
        $category = KBCategory::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/kb/categories/{$category->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);

        expect(KBCategory::find($category->id))->toBeNull();
    });

    it('prevents deletion of category with articles', function () {
        $category = KBCategory::factory()->create(['article_count' => 1]);

        KBArticle::factory()
            ->for($category, 'category')
            ->create();

        $response = $this->deleteJson("/api/v1/admin/kb/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        expect(KBCategory::find($category->id))->not->toBeNull();
    });

    it('prevents deletion of category with children', function () {
        $parent = KBCategory::factory()->create();
        KBCategory::factory()->childOf($parent)->create();

        $response = $this->deleteJson("/api/v1/admin/kb/categories/{$parent->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });
});

describe('PUT /api/v1/admin/kb/categories/order', function () {
    it('updates category order', function () {
        $cat1 = KBCategory::factory()->create(['sort_order' => 1]);
        $cat2 = KBCategory::factory()->create(['sort_order' => 2]);
        $cat3 = KBCategory::factory()->create(['sort_order' => 3]);

        $response = $this->putJson('/api/v1/admin/kb/categories/order', [
            'order' => [
                ['id' => $cat3->id, 'sort_order' => 1],
                ['id' => $cat1->id, 'sort_order' => 2],
                ['id' => $cat2->id, 'sort_order' => 3],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Category order updated successfully',
            ]);

        $cat1->refresh();
        $cat2->refresh();
        $cat3->refresh();

        expect($cat3->sort_order)->toBe(1);
        expect($cat1->sort_order)->toBe(2);
        expect($cat2->sort_order)->toBe(3);
    });

    it('validates order data', function () {
        $response = $this->putJson('/api/v1/admin/kb/categories/order', [
            'order' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    });

    it('validates category ids exist', function () {
        $response = $this->putJson('/api/v1/admin/kb/categories/order', [
            'order' => [
                ['id' => '00000000-0000-0000-0000-000000000000', 'sort_order' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order.0.id']);
    });
});
