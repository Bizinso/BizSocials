<?php

declare(strict_types=1);

/**
 * SupportAttachmentType Enum Unit Tests
 *
 * Tests for the SupportAttachmentType enum which defines attachment types.
 *
 * @see \App\Enums\Support\SupportAttachmentType
 */

use App\Enums\Support\SupportAttachmentType;

test('has all expected cases', function (): void {
    $cases = SupportAttachmentType::cases();

    expect($cases)->toHaveCount(5)
        ->and(SupportAttachmentType::IMAGE->value)->toBe('image')
        ->and(SupportAttachmentType::DOCUMENT->value)->toBe('document')
        ->and(SupportAttachmentType::VIDEO->value)->toBe('video')
        ->and(SupportAttachmentType::ARCHIVE->value)->toBe('archive')
        ->and(SupportAttachmentType::OTHER->value)->toBe('other');
});

test('label returns correct labels', function (): void {
    expect(SupportAttachmentType::IMAGE->label())->toBe('Image')
        ->and(SupportAttachmentType::DOCUMENT->label())->toBe('Document')
        ->and(SupportAttachmentType::VIDEO->label())->toBe('Video')
        ->and(SupportAttachmentType::ARCHIVE->label())->toBe('Archive')
        ->and(SupportAttachmentType::OTHER->label())->toBe('Other');
});

test('allowedExtensions returns array for all types', function (): void {
    foreach (SupportAttachmentType::cases() as $type) {
        expect($type->allowedExtensions())->toBeArray();
    }
});

test('allowedExtensions returns correct extensions for IMAGE', function (): void {
    $extensions = SupportAttachmentType::IMAGE->allowedExtensions();

    expect($extensions)->toContain('png')
        ->and($extensions)->toContain('jpg')
        ->and($extensions)->toContain('jpeg')
        ->and($extensions)->toContain('gif');
});

test('allowedExtensions returns correct extensions for DOCUMENT', function (): void {
    $extensions = SupportAttachmentType::DOCUMENT->allowedExtensions();

    expect($extensions)->toContain('pdf')
        ->and($extensions)->toContain('doc')
        ->and($extensions)->toContain('docx')
        ->and($extensions)->toContain('xls');
});

test('allowedExtensions returns empty array for OTHER', function (): void {
    expect(SupportAttachmentType::OTHER->allowedExtensions())->toBeEmpty();
});

test('maxSizeBytes returns positive integer for all types', function (): void {
    foreach (SupportAttachmentType::cases() as $type) {
        expect($type->maxSizeBytes())->toBeInt()->toBeGreaterThan(0);
    }
});

test('maxSizeBytes returns correct sizes', function (): void {
    expect(SupportAttachmentType::IMAGE->maxSizeBytes())->toBe(10 * 1024 * 1024)
        ->and(SupportAttachmentType::DOCUMENT->maxSizeBytes())->toBe(25 * 1024 * 1024)
        ->and(SupportAttachmentType::VIDEO->maxSizeBytes())->toBe(100 * 1024 * 1024)
        ->and(SupportAttachmentType::ARCHIVE->maxSizeBytes())->toBe(50 * 1024 * 1024);
});

test('values returns all enum values', function (): void {
    $values = SupportAttachmentType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('image')
        ->and($values)->toContain('document');
});
