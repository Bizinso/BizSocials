<?php

declare(strict_types=1);

/**
 * PaginationData Unit Tests
 *
 * Tests for the PaginationData data transfer object which represents
 * pagination metadata from Laravel's paginator.
 *
 * @see \App\Data\Shared\PaginationData
 */

use App\Data\Shared\PaginationData;
use Illuminate\Pagination\LengthAwarePaginator;

test('can create pagination data with constructor', function (): void {
    $pagination = new PaginationData(
        current_page: 2,
        last_page: 5,
        per_page: 15,
        total: 75,
        from: 16,
        to: 30
    );

    expect($pagination->current_page)->toBe(2)
        ->and($pagination->last_page)->toBe(5)
        ->and($pagination->per_page)->toBe(15)
        ->and($pagination->total)->toBe(75)
        ->and($pagination->from)->toBe(16)
        ->and($pagination->to)->toBe(30);
});

test('can create from paginator with items', function (): void {
    $items = collect([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ]);

    $paginator = new LengthAwarePaginator(
        items: $items,
        total: 100,
        perPage: 10,
        currentPage: 3
    );

    $pagination = PaginationData::fromPaginator($paginator);

    expect($pagination)->toBeInstanceOf(PaginationData::class)
        ->and($pagination->current_page)->toBe(3)
        ->and($pagination->last_page)->toBe(10)
        ->and($pagination->per_page)->toBe(10)
        ->and($pagination->total)->toBe(100)
        ->and($pagination->from)->toBe(21)
        ->and($pagination->to)->toBe(23);
});

test('can create from paginator with empty results', function (): void {
    $paginator = new LengthAwarePaginator(
        items: collect([]),
        total: 0,
        perPage: 15,
        currentPage: 1
    );

    $pagination = PaginationData::fromPaginator($paginator);

    expect($pagination->current_page)->toBe(1)
        ->and($pagination->last_page)->toBe(1)
        ->and($pagination->per_page)->toBe(15)
        ->and($pagination->total)->toBe(0)
        ->and($pagination->from)->toBeNull()
        ->and($pagination->to)->toBeNull();
});

test('can create from first page paginator', function (): void {
    $items = collect(range(1, 10));

    $paginator = new LengthAwarePaginator(
        items: $items,
        total: 50,
        perPage: 10,
        currentPage: 1
    );

    $pagination = PaginationData::fromPaginator($paginator);

    expect($pagination->current_page)->toBe(1)
        ->and($pagination->last_page)->toBe(5)
        ->and($pagination->from)->toBe(1)
        ->and($pagination->to)->toBe(10);
});

test('can create from last page paginator', function (): void {
    $items = collect(range(1, 5));

    $paginator = new LengthAwarePaginator(
        items: $items,
        total: 45,
        perPage: 10,
        currentPage: 5
    );

    $pagination = PaginationData::fromPaginator($paginator);

    expect($pagination->current_page)->toBe(5)
        ->and($pagination->last_page)->toBe(5)
        ->and($pagination->from)->toBe(41)
        ->and($pagination->to)->toBe(45);
});

test('from and to are nullable for empty pages', function (): void {
    $pagination = new PaginationData(
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: null,
        to: null
    );

    expect($pagination->from)->toBeNull()
        ->and($pagination->to)->toBeNull();
});

test('can convert to array', function (): void {
    $pagination = new PaginationData(
        current_page: 1,
        last_page: 3,
        per_page: 20,
        total: 55,
        from: 1,
        to: 20
    );

    $array = $pagination->toArray();

    expect($array)->toBeArray()
        ->and($array['current_page'])->toBe(1)
        ->and($array['last_page'])->toBe(3)
        ->and($array['per_page'])->toBe(20)
        ->and($array['total'])->toBe(55)
        ->and($array['from'])->toBe(1)
        ->and($array['to'])->toBe(20);
});

test('is a final class', function (): void {
    $reflection = new ReflectionClass(PaginationData::class);

    expect($reflection->isFinal())->toBeTrue();
});
