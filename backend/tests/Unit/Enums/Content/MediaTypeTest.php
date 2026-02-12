<?php

declare(strict_types=1);

/**
 * MediaType Enum Unit Tests
 *
 * Tests for the MediaType enum which defines the type of
 * media attachment for a post.
 *
 * @see \App\Enums\Content\MediaType
 */

use App\Enums\Content\MediaType;

test('has all expected cases', function (): void {
    $cases = MediaType::cases();

    expect($cases)->toHaveCount(4)
        ->and(MediaType::IMAGE->value)->toBe('image')
        ->and(MediaType::VIDEO->value)->toBe('video')
        ->and(MediaType::GIF->value)->toBe('gif')
        ->and(MediaType::DOCUMENT->value)->toBe('document');
});

test('label returns correct labels', function (): void {
    expect(MediaType::IMAGE->label())->toBe('Image')
        ->and(MediaType::VIDEO->label())->toBe('Video')
        ->and(MediaType::GIF->label())->toBe('GIF')
        ->and(MediaType::DOCUMENT->label())->toBe('Document');
});

test('maxFileSize returns correct values for IMAGE', function (): void {
    $maxSize = MediaType::IMAGE->maxFileSize();

    expect($maxSize)->toBe(10 * 1024 * 1024); // 10 MB
});

test('maxFileSize returns correct values for VIDEO', function (): void {
    $maxSize = MediaType::VIDEO->maxFileSize();

    expect($maxSize)->toBe(500 * 1024 * 1024); // 500 MB
});

test('maxFileSize returns correct values for GIF', function (): void {
    $maxSize = MediaType::GIF->maxFileSize();

    expect($maxSize)->toBe(15 * 1024 * 1024); // 15 MB
});

test('maxFileSize returns correct values for DOCUMENT', function (): void {
    $maxSize = MediaType::DOCUMENT->maxFileSize();

    expect($maxSize)->toBe(100 * 1024 * 1024); // 100 MB
});

test('allowedMimeTypes for IMAGE returns correct types', function (): void {
    $mimeTypes = MediaType::IMAGE->allowedMimeTypes();

    expect($mimeTypes)->toBeArray()
        ->and($mimeTypes)->toContain('image/jpeg')
        ->and($mimeTypes)->toContain('image/png')
        ->and($mimeTypes)->toContain('image/webp')
        ->and($mimeTypes)->toContain('image/heic')
        ->and($mimeTypes)->toContain('image/heif');
});

test('allowedMimeTypes for VIDEO returns correct types', function (): void {
    $mimeTypes = MediaType::VIDEO->allowedMimeTypes();

    expect($mimeTypes)->toBeArray()
        ->and($mimeTypes)->toContain('video/mp4')
        ->and($mimeTypes)->toContain('video/quicktime')
        ->and($mimeTypes)->toContain('video/x-msvideo')
        ->and($mimeTypes)->toContain('video/webm');
});

test('allowedMimeTypes for GIF returns correct types', function (): void {
    $mimeTypes = MediaType::GIF->allowedMimeTypes();

    expect($mimeTypes)->toBeArray()
        ->and($mimeTypes)->toHaveCount(1)
        ->and($mimeTypes)->toContain('image/gif');
});

test('allowedMimeTypes for DOCUMENT returns correct types', function (): void {
    $mimeTypes = MediaType::DOCUMENT->allowedMimeTypes();

    expect($mimeTypes)->toBeArray()
        ->and($mimeTypes)->toContain('application/pdf')
        ->and($mimeTypes)->toContain('application/msword')
        ->and($mimeTypes)->toContain('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
        ->and($mimeTypes)->toContain('application/vnd.ms-powerpoint')
        ->and($mimeTypes)->toContain('application/vnd.openxmlformats-officedocument.presentationml.presentation');
});

test('values returns all enum values', function (): void {
    $values = MediaType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('image')
        ->and($values)->toContain('video')
        ->and($values)->toContain('gif')
        ->and($values)->toContain('document');
});

test('can create enum from string value', function (): void {
    $type = MediaType::from('image');

    expect($type)->toBe(MediaType::IMAGE);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = MediaType::tryFrom('invalid');

    expect($type)->toBeNull();
});
