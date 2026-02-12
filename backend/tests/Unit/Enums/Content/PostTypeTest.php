<?php

declare(strict_types=1);

/**
 * PostType Enum Unit Tests
 *
 * Tests for the PostType enum which defines the type/format
 * of a social media post.
 *
 * @see \App\Enums\Content\PostType
 */

use App\Enums\Content\PostType;
use App\Enums\Social\SocialPlatform;

test('has all expected cases', function (): void {
    $cases = PostType::cases();

    expect($cases)->toHaveCount(5)
        ->and(PostType::STANDARD->value)->toBe('standard')
        ->and(PostType::REEL->value)->toBe('reel')
        ->and(PostType::STORY->value)->toBe('story')
        ->and(PostType::THREAD->value)->toBe('thread')
        ->and(PostType::ARTICLE->value)->toBe('article');
});

test('label returns correct labels', function (): void {
    expect(PostType::STANDARD->label())->toBe('Standard Post')
        ->and(PostType::REEL->label())->toBe('Reel')
        ->and(PostType::STORY->label())->toBe('Story')
        ->and(PostType::THREAD->label())->toBe('Thread')
        ->and(PostType::ARTICLE->label())->toBe('Article');
});

test('supportedPlatforms for STANDARD returns all platforms', function (): void {
    $platforms = PostType::STANDARD->supportedPlatforms();

    expect($platforms)->toBeArray()
        ->and($platforms)->toHaveCount(4)
        ->and($platforms)->toContain(SocialPlatform::LINKEDIN)
        ->and($platforms)->toContain(SocialPlatform::FACEBOOK)
        ->and($platforms)->toContain(SocialPlatform::INSTAGRAM)
        ->and($platforms)->toContain(SocialPlatform::TWITTER);
});

test('supportedPlatforms for REEL returns Instagram and Facebook', function (): void {
    $platforms = PostType::REEL->supportedPlatforms();

    expect($platforms)->toBeArray()
        ->and($platforms)->toHaveCount(2)
        ->and($platforms)->toContain(SocialPlatform::INSTAGRAM)
        ->and($platforms)->toContain(SocialPlatform::FACEBOOK);
});

test('supportedPlatforms for STORY returns Instagram and Facebook', function (): void {
    $platforms = PostType::STORY->supportedPlatforms();

    expect($platforms)->toBeArray()
        ->and($platforms)->toHaveCount(2)
        ->and($platforms)->toContain(SocialPlatform::INSTAGRAM)
        ->and($platforms)->toContain(SocialPlatform::FACEBOOK);
});

test('supportedPlatforms for THREAD returns only Twitter', function (): void {
    $platforms = PostType::THREAD->supportedPlatforms();

    expect($platforms)->toBeArray()
        ->and($platforms)->toHaveCount(1)
        ->and($platforms)->toContain(SocialPlatform::TWITTER);
});

test('supportedPlatforms for ARTICLE returns only LinkedIn', function (): void {
    $platforms = PostType::ARTICLE->supportedPlatforms();

    expect($platforms)->toBeArray()
        ->and($platforms)->toHaveCount(1)
        ->and($platforms)->toContain(SocialPlatform::LINKEDIN);
});

test('values returns all enum values', function (): void {
    $values = PostType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('standard')
        ->and($values)->toContain('reel')
        ->and($values)->toContain('story')
        ->and($values)->toContain('thread')
        ->and($values)->toContain('article');
});

test('can create enum from string value', function (): void {
    $type = PostType::from('standard');

    expect($type)->toBe(PostType::STANDARD);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = PostType::tryFrom('invalid');

    expect($type)->toBeNull();
});
