<?php

declare(strict_types=1);

use App\Enums\Workspace\Permission;

test('has exactly 64 permissions', function () {
    expect(Permission::cases())->toHaveCount(64);
});

test('has exactly 8 domains', function () {
    $domains = Permission::domains();

    expect($domains)->toHaveCount(8)
        ->toContain('workspace')
        ->toContain('content')
        ->toContain('inbox')
        ->toContain('whatsapp')
        ->toContain('analytics')
        ->toContain('billing')
        ->toContain('settings')
        ->toContain('ai');
});

test('domain counts match design', function () {
    expect(Permission::forDomain('workspace'))->toHaveCount(10)
        ->and(Permission::forDomain('content'))->toHaveCount(15)
        ->and(Permission::forDomain('inbox'))->toHaveCount(11)
        ->and(Permission::forDomain('whatsapp'))->toHaveCount(11)
        ->and(Permission::forDomain('analytics'))->toHaveCount(6)
        ->and(Permission::forDomain('billing'))->toHaveCount(4)
        ->and(Permission::forDomain('settings'))->toHaveCount(6)
        ->and(Permission::forDomain('ai'))->toHaveCount(1);
});

test('domain() extracts correct domain', function () {
    expect(Permission::WORKSPACE_SETTINGS_VIEW->domain())->toBe('workspace')
        ->and(Permission::CONTENT_POSTS_CREATE->domain())->toBe('content')
        ->and(Permission::INBOX_ITEMS_VIEW->domain())->toBe('inbox')
        ->and(Permission::WHATSAPP_CONVERSATIONS_VIEW->domain())->toBe('whatsapp')
        ->and(Permission::ANALYTICS_DASHBOARD_VIEW->domain())->toBe('analytics')
        ->and(Permission::BILLING_SUBSCRIPTION_VIEW->domain())->toBe('billing')
        ->and(Permission::SETTINGS_SECURITY_VIEW->domain())->toBe('settings')
        ->and(Permission::AI_ASSIST_USE->domain())->toBe('ai');
});

test('action() extracts correct action', function () {
    expect(Permission::WORKSPACE_SETTINGS_VIEW->action())->toBe('settings.view')
        ->and(Permission::CONTENT_POSTS_EDIT_ANY->action())->toBe('posts.edit_any')
        ->and(Permission::WORKSPACE_DELETE->action())->toBe('delete')
        ->and(Permission::AI_ASSIST_USE->action())->toBe('assist.use');
});

test('description() returns non-empty string for all permissions', function () {
    foreach (Permission::cases() as $permission) {
        expect($permission->description())->toBeString()->not->toBeEmpty();
    }
});

test('fromString returns Permission for valid string', function () {
    expect(Permission::fromString('content.posts.approve'))->toBe(Permission::CONTENT_POSTS_APPROVE);
});

test('fromString returns null for unknown string', function () {
    expect(Permission::fromString('unknown.permission'))->toBeNull()
        ->and(Permission::fromString(''))->toBeNull()
        ->and(Permission::fromString('fake.thing.here'))->toBeNull();
});

test('values returns all 64 string values', function () {
    $values = Permission::values();

    expect($values)->toHaveCount(64)
        ->toContain('workspace.settings.view')
        ->toContain('content.posts.approve')
        ->toContain('billing.subscription.manage')
        ->toContain('ai.assist.use');
});
