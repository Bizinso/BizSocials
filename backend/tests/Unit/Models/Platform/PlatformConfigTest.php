<?php

declare(strict_types=1);

/**
 * PlatformConfig Model Unit Tests
 *
 * Tests for the PlatformConfig model which represents global
 * platform configuration settings managed by super admin users.
 *
 * @see \App\Models\Platform\PlatformConfig
 */

use App\Enums\Platform\ConfigCategory;
use App\Models\Platform\PlatformConfig;
use App\Models\Platform\SuperAdminUser;

test('can create config', function (): void {
    $config = PlatformConfig::create([
        'key' => 'test.config.key',
        'value' => ['value' => 'test value'],
        'category' => ConfigCategory::GENERAL,
        'description' => 'Test configuration',
        'is_sensitive' => false,
    ]);

    expect($config)->toBeInstanceOf(PlatformConfig::class)
        ->and($config->key)->toBe('test.config.key')
        ->and($config->category)->toBe(ConfigCategory::GENERAL)
        ->and($config->id)->not->toBeNull();
});

test('key must be unique', function (): void {
    PlatformConfig::factory()->create(['key' => 'unique.key']);

    expect(fn () => PlatformConfig::factory()->create(['key' => 'unique.key']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('value casts to array', function (): void {
    $config = PlatformConfig::factory()->create([
        'value' => ['setting' => 'value', 'nested' => ['data' => true]],
    ]);

    expect($config->value)->toBeArray()
        ->and($config->value)->toHaveKey('setting')
        ->and($config->value['setting'])->toBe('value')
        ->and($config->value['nested']['data'])->toBeTrue();
});

test('category casts to enum', function (): void {
    $config = PlatformConfig::factory()->create([
        'category' => ConfigCategory::SECURITY,
    ]);

    expect($config->category)->toBeInstanceOf(ConfigCategory::class)
        ->and($config->category)->toBe(ConfigCategory::SECURITY);
});

test('get value returns config', function (): void {
    PlatformConfig::factory()->create([
        'key' => 'platform.name',
        'value' => ['value' => 'BizSocials'],
    ]);

    $value = PlatformConfig::getValue('platform.name');

    expect($value)->toBe('BizSocials');
});

test('get value returns default when not found', function (): void {
    $value = PlatformConfig::getValue('nonexistent.key', 'default_value');

    expect($value)->toBe('default_value');
});

test('get value returns null when not found and no default', function (): void {
    $value = PlatformConfig::getValue('nonexistent.key');

    expect($value)->toBeNull();
});

test('scope by category filters correctly', function (): void {
    PlatformConfig::factory()->count(3)->create([
        'category' => ConfigCategory::GENERAL,
    ]);
    PlatformConfig::factory()->count(2)->create([
        'category' => ConfigCategory::SECURITY,
    ]);
    PlatformConfig::factory()->count(1)->create([
        'category' => ConfigCategory::LIMITS,
    ]);

    $generalConfigs = PlatformConfig::byCategory(ConfigCategory::GENERAL)->get();
    $securityConfigs = PlatformConfig::byCategory(ConfigCategory::SECURITY)->get();
    $limitsConfigs = PlatformConfig::byCategory(ConfigCategory::LIMITS)->get();

    expect($generalConfigs)->toHaveCount(3)
        ->and($securityConfigs)->toHaveCount(2)
        ->and($limitsConfigs)->toHaveCount(1);
});

test('belongs to super admin', function (): void {
    $admin = SuperAdminUser::factory()->create();

    $config = PlatformConfig::factory()->create([
        'updated_by' => $admin->id,
    ]);

    expect($config->updatedByAdmin)->toBeInstanceOf(SuperAdminUser::class)
        ->and($config->updatedByAdmin->id)->toBe($admin->id);
});

test('updated by admin can be null', function (): void {
    $config = PlatformConfig::factory()->create([
        'updated_by' => null,
    ]);

    expect($config->updatedByAdmin)->toBeNull();
});

test('set value creates new config', function (): void {
    $config = PlatformConfig::setValue(
        'new.config.key',
        'test value',
        ConfigCategory::GENERAL
    );

    expect($config)->toBeInstanceOf(PlatformConfig::class)
        ->and($config->key)->toBe('new.config.key')
        ->and(PlatformConfig::getValue('new.config.key'))->toBe('test value');
});

test('set value updates existing config', function (): void {
    PlatformConfig::setValue('existing.key', 'original value', ConfigCategory::GENERAL);
    PlatformConfig::setValue('existing.key', 'updated value');

    expect(PlatformConfig::getValue('existing.key'))->toBe('updated value')
        ->and(PlatformConfig::where('key', 'existing.key')->count())->toBe(1);
});

test('is sensitive casts to boolean', function (): void {
    $sensitiveConfig = PlatformConfig::factory()->sensitive()->create();
    $normalConfig = PlatformConfig::factory()->create(['is_sensitive' => false]);

    expect($sensitiveConfig->is_sensitive)->toBeTrue()
        ->and($normalConfig->is_sensitive)->toBeFalse();
});

test('factory creates valid model', function (): void {
    $config = PlatformConfig::factory()->create();

    expect($config)->toBeInstanceOf(PlatformConfig::class)
        ->and($config->id)->not->toBeNull()
        ->and($config->key)->toBeString()
        ->and($config->value)->toBeArray()
        ->and($config->category)->toBeInstanceOf(ConfigCategory::class);
});
