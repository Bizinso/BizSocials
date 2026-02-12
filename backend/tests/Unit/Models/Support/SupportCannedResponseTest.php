<?php

declare(strict_types=1);

/**
 * SupportCannedResponse Model Unit Tests
 *
 * Tests for the SupportCannedResponse model.
 *
 * @see \App\Models\Support\SupportCannedResponse
 */

use App\Enums\Support\CannedResponseCategory;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCannedResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create canned response with factory', function (): void {
    $response = SupportCannedResponse::factory()->create();

    expect($response)->toBeInstanceOf(SupportCannedResponse::class)
        ->and($response->id)->not->toBeNull()
        ->and($response->title)->not->toBeNull()
        ->and($response->content)->not->toBeNull();
});

test('has correct table name', function (): void {
    $response = new SupportCannedResponse();

    expect($response->getTable())->toBe('support_canned_responses');
});

test('casts attributes correctly', function (): void {
    $response = SupportCannedResponse::factory()->create();

    expect($response->category)->toBeInstanceOf(CannedResponseCategory::class)
        ->and($response->is_shared)->toBeBool()
        ->and($response->usage_count)->toBeInt();
});

test('creator relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $response = SupportCannedResponse::factory()->createdBy($admin)->create();

    expect($response->creator)->toBeInstanceOf(SuperAdminUser::class)
        ->and($response->creator->id)->toBe($admin->id);
});

test('shared scope filters shared responses', function (): void {
    SupportCannedResponse::factory()->shared()->count(2)->create();
    SupportCannedResponse::factory()->personal()->create();

    expect(SupportCannedResponse::shared()->count())->toBe(2);
});

test('byCategory scope filters by category', function (): void {
    SupportCannedResponse::factory()->greeting()->count(2)->create();
    SupportCannedResponse::factory()->billing()->create();

    expect(SupportCannedResponse::byCategory(CannedResponseCategory::GREETING)->count())->toBe(2);
});

test('byCreator scope filters by creator', function (): void {
    $admin = SuperAdminUser::factory()->create();
    SupportCannedResponse::factory()->createdBy($admin)->count(2)->create();
    SupportCannedResponse::factory()->create();

    expect(SupportCannedResponse::byCreator($admin->id)->count())->toBe(2);
});

test('search scope filters by title, content, or shortcut', function (): void {
    SupportCannedResponse::factory()->create(['title' => 'Welcome greeting']);
    SupportCannedResponse::factory()->create(['content' => 'Thank you for greeting us']);
    SupportCannedResponse::factory()->create(['shortcut' => 'greet']);
    SupportCannedResponse::factory()->create(['title' => 'Closing message']);

    expect(SupportCannedResponse::search('greet')->count())->toBe(3);
});

test('popular scope orders by usage count descending', function (): void {
    SupportCannedResponse::factory()->create(['usage_count' => 10]);
    SupportCannedResponse::factory()->create(['usage_count' => 50]);
    SupportCannedResponse::factory()->create(['usage_count' => 30]);

    $responses = SupportCannedResponse::popular()->get();

    expect($responses->first()->usage_count)->toBe(50)
        ->and($responses->last()->usage_count)->toBe(10);
});

test('isShared returns correct value', function (): void {
    $shared = SupportCannedResponse::factory()->shared()->create();
    $personal = SupportCannedResponse::factory()->personal()->create();

    expect($shared->isShared())->toBeTrue()
        ->and($personal->isShared())->toBeFalse();
});

test('incrementUsageCount increases usage count', function (): void {
    $response = SupportCannedResponse::factory()->create(['usage_count' => 5]);

    $response->incrementUsageCount();

    expect($response->fresh()->usage_count)->toBe(6);
});

test('renderContent returns content without variables', function (): void {
    $response = SupportCannedResponse::factory()->create([
        'content' => 'Hello! How can I help you today?',
    ]);

    expect($response->renderContent())->toBe('Hello! How can I help you today?');
});

test('renderContent substitutes variables', function (): void {
    $response = SupportCannedResponse::factory()->create([
        'content' => 'Hello {name}, your order {order_id} has shipped.',
    ]);

    $rendered = $response->renderContent([
        'name' => 'John',
        'order_id' => '12345',
    ]);

    expect($rendered)->toBe('Hello John, your order 12345 has shipped.');
});

test('renderContent handles missing variables gracefully', function (): void {
    $response = SupportCannedResponse::factory()->create([
        'content' => 'Hello {name}, your order {order_id} has shipped.',
    ]);

    $rendered = $response->renderContent(['name' => 'John']);

    expect($rendered)->toBe('Hello John, your order {order_id} has shipped.');
});

test('greeting state creates greeting response', function (): void {
    $response = SupportCannedResponse::factory()->greeting()->create();

    expect($response->category)->toBe(CannedResponseCategory::GREETING)
        ->and($response->content)->toContain('Hello');
});

test('closing state creates closing response', function (): void {
    $response = SupportCannedResponse::factory()->closing()->create();

    expect($response->category)->toBe(CannedResponseCategory::CLOSING)
        ->and($response->content)->toContain('questions');
});

test('popular state sets high usage count', function (): void {
    $response = SupportCannedResponse::factory()->popular()->create();

    expect($response->usage_count)->toBeGreaterThanOrEqual(50);
});
