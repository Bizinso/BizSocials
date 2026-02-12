<?php

declare(strict_types=1);

/**
 * SupportCategory Model Unit Tests
 *
 * Tests for the SupportCategory model.
 *
 * @see \App\Models\Support\SupportCategory
 */

use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create category with factory', function (): void {
    $category = SupportCategory::factory()->create();

    expect($category)->toBeInstanceOf(SupportCategory::class)
        ->and($category->id)->not->toBeNull()
        ->and($category->name)->not->toBeNull()
        ->and($category->slug)->not->toBeNull();
});

test('has correct table name', function (): void {
    $category = new SupportCategory();

    expect($category->getTable())->toBe('support_categories');
});

test('casts attributes correctly', function (): void {
    $category = SupportCategory::factory()->create();

    expect($category->sort_order)->toBeInt()
        ->and($category->is_active)->toBeBool()
        ->and($category->ticket_count)->toBeInt();
});

test('parent relationship works', function (): void {
    $parent = SupportCategory::factory()->create();
    $child = SupportCategory::factory()->child($parent)->create();

    expect($child->parent)->toBeInstanceOf(SupportCategory::class)
        ->and($child->parent->id)->toBe($parent->id);
});

test('children relationship works', function (): void {
    $parent = SupportCategory::factory()->create();
    SupportCategory::factory()->child($parent)->count(3)->create();

    expect($parent->children)->toHaveCount(3)
        ->and($parent->children->first())->toBeInstanceOf(SupportCategory::class);
});

test('tickets relationship works', function (): void {
    $category = SupportCategory::factory()->create();
    SupportTicket::factory()->inCategory($category)->count(2)->create();

    expect($category->tickets)->toHaveCount(2)
        ->and($category->tickets->first())->toBeInstanceOf(SupportTicket::class);
});

test('active scope filters active categories', function (): void {
    SupportCategory::factory()->active()->count(2)->create();
    SupportCategory::factory()->inactive()->create();

    expect(SupportCategory::active()->count())->toBe(2);
});

test('roots scope filters root categories', function (): void {
    $root1 = SupportCategory::factory()->root()->create();
    $root2 = SupportCategory::factory()->root()->create();
    SupportCategory::factory()->child($root1)->create();

    expect(SupportCategory::roots()->count())->toBe(2);
});

test('ordered scope orders by sort_order', function (): void {
    SupportCategory::factory()->create(['sort_order' => 3]);
    SupportCategory::factory()->create(['sort_order' => 1]);
    SupportCategory::factory()->create(['sort_order' => 2]);

    $categories = SupportCategory::ordered()->get();

    expect($categories->first()->sort_order)->toBe(1)
        ->and($categories->last()->sort_order)->toBe(3);
});

test('isRoot returns true for root category', function (): void {
    $root = SupportCategory::factory()->root()->create();
    $parent = SupportCategory::factory()->create();
    $child = SupportCategory::factory()->child($parent)->create();

    expect($root->isRoot())->toBeTrue()
        ->and($child->isRoot())->toBeFalse();
});

test('hasChildren returns true when has children', function (): void {
    $parent = SupportCategory::factory()->create();
    SupportCategory::factory()->child($parent)->create();

    $childless = SupportCategory::factory()->create();

    expect($parent->hasChildren())->toBeTrue()
        ->and($childless->hasChildren())->toBeFalse();
});

test('getFullPath returns correct path', function (): void {
    $parent = SupportCategory::factory()->create(['name' => 'Parent']);
    $child = SupportCategory::factory()->child($parent)->create(['name' => 'Child']);

    expect($parent->getFullPath())->toBe('Parent')
        ->and($child->getFullPath())->toBe('Parent > Child');
});

test('incrementTicketCount increases ticket count', function (): void {
    $category = SupportCategory::factory()->create(['ticket_count' => 5]);

    $category->incrementTicketCount();

    expect($category->fresh()->ticket_count)->toBe(6);
});

test('decrementTicketCount decreases ticket count', function (): void {
    $category = SupportCategory::factory()->create(['ticket_count' => 5]);

    $category->decrementTicketCount();

    expect($category->fresh()->ticket_count)->toBe(4);
});
