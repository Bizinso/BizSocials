<?php

declare(strict_types=1);

/**
 * KBCategory Model Unit Tests
 *
 * Tests for the KBCategory model which represents knowledge base categories.
 *
 * @see \App\Models\KnowledgeBase\KBCategory
 */

use App\Enums\KnowledgeBase\KBVisibility;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create category with factory', function (): void {
    $category = KBCategory::factory()->create();

    expect($category)->toBeInstanceOf(KBCategory::class)
        ->and($category->id)->not->toBeNull()
        ->and($category->name)->not->toBeNull()
        ->and($category->slug)->not->toBeNull();
});

test('has correct table name', function (): void {
    $category = new KBCategory();

    expect($category->getTable())->toBe('kb_categories');
});

test('casts attributes correctly', function (): void {
    $category = KBCategory::factory()->create([
        'is_public' => true,
        'visibility' => KBVisibility::ALL,
        'allowed_plans' => ['plan1', 'plan2'],
        'sort_order' => 5,
        'article_count' => 10,
    ]);

    expect($category->is_public)->toBeBool()
        ->and($category->visibility)->toBeInstanceOf(KBVisibility::class)
        ->and($category->allowed_plans)->toBeArray()
        ->and($category->sort_order)->toBeInt()
        ->and($category->article_count)->toBeInt();
});

test('parent relationship works', function (): void {
    $parent = KBCategory::factory()->create();
    $child = KBCategory::factory()->childOf($parent)->create();

    expect($child->parent)->toBeInstanceOf(KBCategory::class)
        ->and($child->parent->id)->toBe($parent->id);
});

test('children relationship works', function (): void {
    $parent = KBCategory::factory()->create();
    KBCategory::factory()->childOf($parent)->count(3)->create();

    expect($parent->children)->toHaveCount(3)
        ->and($parent->children->first())->toBeInstanceOf(KBCategory::class);
});

test('articles relationship works', function (): void {
    $category = KBCategory::factory()->create();
    KBArticle::factory()->forCategory($category)->count(2)->create();

    expect($category->articles)->toHaveCount(2)
        ->and($category->articles->first())->toBeInstanceOf(KBArticle::class);
});

test('published scope filters public categories', function (): void {
    KBCategory::factory()->public()->count(2)->create();
    KBCategory::factory()->private()->create();

    $publicCategories = KBCategory::published()->get();

    expect($publicCategories)->toHaveCount(2);
});

test('topLevel scope filters root categories', function (): void {
    $parent = KBCategory::factory()->create();
    KBCategory::factory()->childOf($parent)->count(2)->create();

    $topLevel = KBCategory::topLevel()->get();

    expect($topLevel)->toHaveCount(1)
        ->and($topLevel->first()->id)->toBe($parent->id);
});

test('ordered scope orders by sort_order and name', function (): void {
    KBCategory::factory()->create(['sort_order' => 3, 'name' => 'Third']);
    KBCategory::factory()->create(['sort_order' => 1, 'name' => 'First']);
    KBCategory::factory()->create(['sort_order' => 2, 'name' => 'Second']);

    $ordered = KBCategory::ordered()->get();

    expect($ordered->first()->sort_order)->toBe(1)
        ->and($ordered->last()->sort_order)->toBe(3);
});

test('isTopLevel returns true for categories without parent', function (): void {
    $topLevel = KBCategory::factory()->topLevel()->create();
    $child = KBCategory::factory()->childOf($topLevel)->create();

    expect($topLevel->isTopLevel())->toBeTrue()
        ->and($child->isTopLevel())->toBeFalse();
});

test('hasChildren returns correct value', function (): void {
    $parent = KBCategory::factory()->create();
    $childless = KBCategory::factory()->create();

    KBCategory::factory()->childOf($parent)->create();

    expect($parent->hasChildren())->toBeTrue()
        ->and($childless->hasChildren())->toBeFalse();
});

test('hasArticles returns correct value', function (): void {
    $withArticles = KBCategory::factory()->withArticleCount(5)->create();
    $withoutArticles = KBCategory::factory()->create(['article_count' => 0]);

    expect($withArticles->hasArticles())->toBeTrue()
        ->and($withoutArticles->hasArticles())->toBeFalse();
});

test('getPath returns path from root to category', function (): void {
    $grandparent = KBCategory::factory()->create(['name' => 'Grandparent']);
    $parent = KBCategory::factory()->childOf($grandparent)->create(['name' => 'Parent']);
    $child = KBCategory::factory()->childOf($parent)->create(['name' => 'Child']);

    $path = $child->getPath();

    expect($path)->toHaveCount(3)
        ->and($path->first()->name)->toBe('Grandparent')
        ->and($path->last()->name)->toBe('Child');
});

test('getDepth returns correct depth', function (): void {
    $root = KBCategory::factory()->create();
    $level1 = KBCategory::factory()->childOf($root)->create();
    $level2 = KBCategory::factory()->childOf($level1)->create();

    expect($root->getDepth())->toBe(0)
        ->and($level1->getDepth())->toBe(1)
        ->and($level2->getDepth())->toBe(2);
});

test('incrementArticleCount increases count', function (): void {
    $category = KBCategory::factory()->create(['article_count' => 5]);

    $category->incrementArticleCount();

    expect($category->fresh()->article_count)->toBe(6);
});

test('decrementArticleCount decreases count', function (): void {
    $category = KBCategory::factory()->create(['article_count' => 5]);

    $category->decrementArticleCount();

    expect($category->fresh()->article_count)->toBe(4);
});

test('decrementArticleCount does not go below zero', function (): void {
    $category = KBCategory::factory()->create(['article_count' => 0]);

    $category->decrementArticleCount();

    expect($category->fresh()->article_count)->toBe(0);
});

test('getFullSlugPath returns concatenated slug path', function (): void {
    $root = KBCategory::factory()->create(['slug' => 'root']);
    $child = KBCategory::factory()->childOf($root)->create(['slug' => 'child']);
    $grandchild = KBCategory::factory()->childOf($child)->create(['slug' => 'grandchild']);

    expect($grandchild->getFullSlugPath())->toBe('root/child/grandchild');
});
