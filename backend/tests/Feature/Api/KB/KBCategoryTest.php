<?php

declare(strict_types=1);

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;

describe('GET /api/v1/kb/categories', function () {
    it('returns all public categories', function () {
        KBCategory::factory()->public()->count(3)->create();
        KBCategory::factory()->create(['is_public' => false]);

        $response = $this->getJson('/api/v1/kb/categories');

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
                        'color',
                        'sort_order',
                        'article_count',
                        'parent_id',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    });

    it('returns categories ordered by sort_order', function () {
        $cat3 = KBCategory::factory()->public()->create(['sort_order' => 3, 'name' => 'Third']);
        $cat1 = KBCategory::factory()->public()->create(['sort_order' => 1, 'name' => 'First']);
        $cat2 = KBCategory::factory()->public()->create(['sort_order' => 2, 'name' => 'Second']);

        $response = $this->getJson('/api/v1/kb/categories');

        $response->assertOk();
        $data = $response->json('data');
        expect($data[0]['name'])->toBe('First');
        expect($data[1]['name'])->toBe('Second');
        expect($data[2]['name'])->toBe('Third');
    });
});

describe('GET /api/v1/kb/categories/tree', function () {
    it('returns hierarchical category tree', function () {
        $parent = KBCategory::factory()->public()->create(['name' => 'Parent']);
        $child1 = KBCategory::factory()->public()->create([
            'name' => 'Child 1',
            'parent_id' => $parent->id,
        ]);
        $child2 = KBCategory::factory()->public()->create([
            'name' => 'Child 2',
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson('/api/v1/kb/categories/tree');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'children',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $parentNode = collect($data)->firstWhere('name', 'Parent');
        expect($parentNode['children'])->toHaveCount(2);
    });
});

describe('GET /api/v1/kb/categories/{slug}', function () {
    it('returns category with paginated articles', function () {
        $category = KBCategory::factory()->public()->create(['slug' => 'test-category']);

        KBArticle::factory()
            ->published()
            ->for($category, 'category')
            ->count(3)
            ->create();

        $response = $this->getJson('/api/v1/kb/categories/test-category');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'articles' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title',
                                'slug',
                            ],
                        ],
                        'meta' => [
                            'current_page',
                            'last_page',
                            'per_page',
                            'total',
                        ],
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent category', function () {
        $response = $this->getJson('/api/v1/kb/categories/non-existent');

        $response->assertNotFound();
    });

    it('returns 404 for non-public category', function () {
        KBCategory::factory()->create([
            'slug' => 'private-category',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/kb/categories/private-category');

        $response->assertNotFound();
    });

    it('only shows published articles in category', function () {
        $category = KBCategory::factory()->public()->create();

        KBArticle::factory()
            ->published()
            ->for($category, 'category')
            ->count(2)
            ->create();

        KBArticle::factory()
            ->draft()
            ->for($category, 'category')
            ->create();

        $response = $this->getJson("/api/v1/kb/categories/{$category->slug}");

        $response->assertOk();
        expect($response->json('data.articles.meta.total'))->toBe(2);
    });
});
