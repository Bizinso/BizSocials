<?php

declare(strict_types=1);

use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCategory;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->create();
});

describe('GET /api/v1/admin/support/categories', function () {
    it('returns all categories including inactive', function () {
        SupportCategory::factory()->active()->count(3)->create();
        SupportCategory::factory()->inactive()->count(2)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/support/categories');

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
                        'color',
                        'icon',
                        'is_active',
                        'sort_order',
                        'ticket_count',
                    ],
                ],
            ])
            ->assertJsonCount(5, 'data');
    });
});

describe('POST /api/v1/admin/support/categories', function () {
    it('creates a new category', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/support/categories', [
                'name' => 'Billing Issues',
                'description' => 'Issues related to billing and payments',
                'color' => '#FF5733',
                'icon' => 'credit-card',
                'sort_order' => 10,
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
                    'color',
                    'icon',
                    'is_active',
                    'sort_order',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Billing Issues',
                    'slug' => 'billing-issues',
                    'is_active' => true,
                ],
            ]);

        expect(SupportCategory::count())->toBe(1);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/support/categories', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates color format', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/support/categories', [
                'name' => 'Test Category',
                'color' => 'invalid-color',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);
    });
});

describe('PUT /api/v1/admin/support/categories/{category}', function () {
    it('updates a category', function () {
        $category = SupportCategory::factory()->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/support/categories/{$category->id}", [
                'name' => 'Updated Name',
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'is_active' => false,
                ],
            ]);
    });

    it('updates category slug when name changes', function () {
        $category = SupportCategory::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/support/categories/{$category->id}", [
                'name' => 'New Category Name',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'slug' => 'new-category-name',
                ],
            ]);
    });
});

describe('DELETE /api/v1/admin/support/categories/{category}', function () {
    it('deletes a category without tickets', function () {
        $category = SupportCategory::factory()->create([
            'ticket_count' => 0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/support/categories/{$category->id}");

        $response->assertOk();

        expect(SupportCategory::find($category->id))->toBeNull();
    });

    it('cannot delete category with tickets', function () {
        $category = SupportCategory::factory()->withTicketCount(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/support/categories/{$category->id}");

        $response->assertUnprocessable();
    });

    it('cannot delete category with children', function () {
        $parent = SupportCategory::factory()->create();
        SupportCategory::factory()->child($parent)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/support/categories/{$parent->id}");

        $response->assertUnprocessable();
    });
});
