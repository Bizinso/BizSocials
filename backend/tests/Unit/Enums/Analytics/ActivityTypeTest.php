<?php

declare(strict_types=1);

/**
 * ActivityType Enum Unit Tests
 *
 * Tests for the ActivityType enum which defines types of user activities
 * tracked for usage analytics.
 *
 * @see \App\Enums\Analytics\ActivityType
 */

use App\Enums\Analytics\ActivityCategory;
use App\Enums\Analytics\ActivityType;

test('has expected values', function (): void {
    $values = array_column(ActivityType::cases(), 'value');

    expect($values)->toContain('post_created')
        ->and($values)->toContain('post_edited')
        ->and($values)->toContain('post_deleted')
        ->and($values)->toContain('post_scheduled')
        ->and($values)->toContain('post_published')
        ->and($values)->toContain('media_uploaded')
        ->and($values)->toContain('inbox_viewed')
        ->and($values)->toContain('reply_sent')
        ->and($values)->toContain('dashboard_viewed')
        ->and($values)->toContain('report_generated')
        ->and($values)->toContain('account_connected')
        ->and($values)->toContain('ai_caption_generated');
});

test('can be created from string', function (): void {
    $type = ActivityType::from('post_created');

    expect($type)->toBe(ActivityType::POST_CREATED);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = ActivityType::tryFrom('invalid_type');

    expect($type)->toBeNull();
});

describe('category method', function (): void {
    test('returns CONTENT_CREATION for content activities', function (): void {
        expect(ActivityType::POST_CREATED->category())->toBe(ActivityCategory::CONTENT_CREATION)
            ->and(ActivityType::POST_EDITED->category())->toBe(ActivityCategory::CONTENT_CREATION)
            ->and(ActivityType::MEDIA_UPLOADED->category())->toBe(ActivityCategory::CONTENT_CREATION);
    });

    test('returns PUBLISHING for publishing activities', function (): void {
        expect(ActivityType::POST_SCHEDULED->category())->toBe(ActivityCategory::PUBLISHING)
            ->and(ActivityType::POST_PUBLISHED->category())->toBe(ActivityCategory::PUBLISHING);
    });

    test('returns ENGAGEMENT for engagement activities', function (): void {
        expect(ActivityType::INBOX_VIEWED->category())->toBe(ActivityCategory::ENGAGEMENT)
            ->and(ActivityType::REPLY_SENT->category())->toBe(ActivityCategory::ENGAGEMENT)
            ->and(ActivityType::COMMENT_LIKED->category())->toBe(ActivityCategory::ENGAGEMENT);
    });

    test('returns ANALYTICS for analytics activities', function (): void {
        expect(ActivityType::DASHBOARD_VIEWED->category())->toBe(ActivityCategory::ANALYTICS)
            ->and(ActivityType::REPORT_GENERATED->category())->toBe(ActivityCategory::ANALYTICS)
            ->and(ActivityType::REPORT_EXPORTED->category())->toBe(ActivityCategory::ANALYTICS);
    });

    test('returns SETTINGS for settings activities', function (): void {
        expect(ActivityType::ACCOUNT_CONNECTED->category())->toBe(ActivityCategory::SETTINGS)
            ->and(ActivityType::ACCOUNT_DISCONNECTED->category())->toBe(ActivityCategory::SETTINGS)
            ->and(ActivityType::SETTINGS_CHANGED->category())->toBe(ActivityCategory::SETTINGS)
            ->and(ActivityType::TEAM_MEMBER_INVITED->category())->toBe(ActivityCategory::SETTINGS);
    });

    test('returns AI_FEATURES for AI activities', function (): void {
        expect(ActivityType::AI_CAPTION_GENERATED->category())->toBe(ActivityCategory::AI_FEATURES)
            ->and(ActivityType::AI_HASHTAG_SUGGESTED->category())->toBe(ActivityCategory::AI_FEATURES)
            ->and(ActivityType::AI_BEST_TIME_CHECKED->category())->toBe(ActivityCategory::AI_FEATURES);
    });

    test('returns AUTHENTICATION for auth activities', function (): void {
        expect(ActivityType::USER_LOGIN->category())->toBe(ActivityCategory::AUTHENTICATION)
            ->and(ActivityType::USER_LOGOUT->category())->toBe(ActivityCategory::AUTHENTICATION);
    });
});

describe('label method', function (): void {
    test('returns human-readable labels', function (): void {
        expect(ActivityType::POST_CREATED->label())->toBe('Post Created')
            ->and(ActivityType::DASHBOARD_VIEWED->label())->toBe('Dashboard Viewed')
            ->and(ActivityType::AI_CAPTION_GENERATED->label())->toBe('AI Caption Generated');
    });
});

describe('description method', function (): void {
    test('returns descriptive text', function (): void {
        expect(ActivityType::POST_CREATED->description())->toBeString()
            ->and(ActivityType::POST_CREATED->description())->not->toBeEmpty();
    });
});

describe('isContentActivity method', function (): void {
    test('returns true for content activities', function (): void {
        expect(ActivityType::POST_CREATED->isContentActivity())->toBeTrue()
            ->and(ActivityType::POST_EDITED->isContentActivity())->toBeTrue()
            ->and(ActivityType::MEDIA_UPLOADED->isContentActivity())->toBeTrue();
    });

    test('returns false for non-content activities', function (): void {
        expect(ActivityType::DASHBOARD_VIEWED->isContentActivity())->toBeFalse()
            ->and(ActivityType::ACCOUNT_CONNECTED->isContentActivity())->toBeFalse();
    });
});

describe('isAiActivity method', function (): void {
    test('returns true for AI activities', function (): void {
        expect(ActivityType::AI_CAPTION_GENERATED->isAiActivity())->toBeTrue()
            ->and(ActivityType::AI_HASHTAG_SUGGESTED->isAiActivity())->toBeTrue()
            ->and(ActivityType::AI_BEST_TIME_CHECKED->isAiActivity())->toBeTrue();
    });

    test('returns false for non-AI activities', function (): void {
        expect(ActivityType::POST_CREATED->isAiActivity())->toBeFalse()
            ->and(ActivityType::DASHBOARD_VIEWED->isAiActivity())->toBeFalse();
    });
});

test('all cases have unique values', function (): void {
    $values = array_column(ActivityType::cases(), 'value');

    expect(count($values))->toBe(count(array_unique($values)));
});
