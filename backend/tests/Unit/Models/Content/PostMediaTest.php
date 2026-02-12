<?php

declare(strict_types=1);

/**
 * PostMedia Model Unit Tests
 *
 * Tests for the PostMedia model which represents a media attachment
 * for a post.
 *
 * @see \App\Models\Content\PostMedia
 */

use App\Enums\Content\MediaProcessingStatus;
use App\Enums\Content\MediaType;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $media = new PostMedia();

    expect($media->getTable())->toBe('post_media');
});

test('uses uuid primary key', function (): void {
    $media = PostMedia::factory()->create();

    expect($media->id)->not->toBeNull()
        ->and(strlen($media->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $media = new PostMedia();
    $fillable = $media->getFillable();

    expect($fillable)->toContain('post_id')
        ->and($fillable)->toContain('type')
        ->and($fillable)->toContain('file_name')
        ->and($fillable)->toContain('file_size')
        ->and($fillable)->toContain('mime_type')
        ->and($fillable)->toContain('storage_path')
        ->and($fillable)->toContain('cdn_url')
        ->and($fillable)->toContain('thumbnail_url')
        ->and($fillable)->toContain('dimensions')
        ->and($fillable)->toContain('duration_seconds')
        ->and($fillable)->toContain('alt_text')
        ->and($fillable)->toContain('sort_order')
        ->and($fillable)->toContain('processing_status')
        ->and($fillable)->toContain('metadata');
});

test('type casts to enum', function (): void {
    $media = PostMedia::factory()->image()->create();

    expect($media->type)->toBeInstanceOf(MediaType::class)
        ->and($media->type)->toBe(MediaType::IMAGE);
});

test('processing_status casts to enum', function (): void {
    $media = PostMedia::factory()->completed()->create();

    expect($media->processing_status)->toBeInstanceOf(MediaProcessingStatus::class)
        ->and($media->processing_status)->toBe(MediaProcessingStatus::COMPLETED);
});

test('dimensions casts to array', function (): void {
    $dimensions = ['width' => 1920, 'height' => 1080];
    $media = PostMedia::factory()->create(['dimensions' => $dimensions]);

    expect($media->dimensions)->toBeArray()
        ->and($media->dimensions['width'])->toBe(1920)
        ->and($media->dimensions['height'])->toBe(1080);
});

test('file_size casts to integer', function (): void {
    $media = PostMedia::factory()->create(['file_size' => 1000000]);

    expect($media->file_size)->toBeInt()
        ->and($media->file_size)->toBe(1000000);
});

test('duration_seconds casts to integer', function (): void {
    $media = PostMedia::factory()->video()->create(['duration_seconds' => 60]);

    expect($media->duration_seconds)->toBeInt()
        ->and($media->duration_seconds)->toBe(60);
});

test('sort_order casts to integer', function (): void {
    $media = PostMedia::factory()->create(['sort_order' => 2]);

    expect($media->sort_order)->toBeInt()
        ->and($media->sort_order)->toBe(2);
});

test('post relationship returns belongs to', function (): void {
    $media = new PostMedia();

    expect($media->post())->toBeInstanceOf(BelongsTo::class);
});

test('post relationship works correctly', function (): void {
    $post = Post::factory()->create();
    $media = PostMedia::factory()->forPost($post)->create();

    expect($media->post)->toBeInstanceOf(Post::class)
        ->and($media->post->id)->toBe($post->id);
});

test('scope forPost filters correctly', function (): void {
    $post1 = Post::factory()->create();
    $post2 = Post::factory()->create();

    PostMedia::factory()->count(3)->forPost($post1)->create();
    PostMedia::factory()->count(2)->forPost($post2)->create();

    $media = PostMedia::forPost($post1->id)->get();

    expect($media)->toHaveCount(3)
        ->and($media->every(fn ($m) => $m->post_id === $post1->id))->toBeTrue();
});

test('scope images filters images only', function (): void {
    PostMedia::factory()->image()->create();
    PostMedia::factory()->image()->create();
    PostMedia::factory()->video()->create();

    $images = PostMedia::images()->get();

    expect($images)->toHaveCount(2)
        ->and($images->every(fn ($m) => $m->type === MediaType::IMAGE))->toBeTrue();
});

test('scope videos filters videos only', function (): void {
    PostMedia::factory()->image()->create();
    PostMedia::factory()->video()->create();
    PostMedia::factory()->video()->create();

    $videos = PostMedia::videos()->get();

    expect($videos)->toHaveCount(2)
        ->and($videos->every(fn ($m) => $m->type === MediaType::VIDEO))->toBeTrue();
});

test('scope ready filters completed processing status', function (): void {
    PostMedia::factory()->completed()->create();
    PostMedia::factory()->completed()->create();
    PostMedia::factory()->pending()->create();
    PostMedia::factory()->processing()->create();

    $ready = PostMedia::ready()->get();

    expect($ready)->toHaveCount(2)
        ->and($ready->every(fn ($m) => $m->processing_status === MediaProcessingStatus::COMPLETED))->toBeTrue();
});

test('isReady returns correct value', function (): void {
    $completed = PostMedia::factory()->completed()->create();
    $pending = PostMedia::factory()->pending()->create();

    expect($completed->isReady())->toBeTrue()
        ->and($pending->isReady())->toBeFalse();
});

test('isProcessing returns correct value', function (): void {
    $processing = PostMedia::factory()->processing()->create();
    $completed = PostMedia::factory()->completed()->create();

    expect($processing->isProcessing())->toBeTrue()
        ->and($completed->isProcessing())->toBeFalse();
});

test('hasFailed returns correct value', function (): void {
    $failed = PostMedia::factory()->processingFailed()->create();
    $completed = PostMedia::factory()->completed()->create();

    expect($failed->hasFailed())->toBeTrue()
        ->and($completed->hasFailed())->toBeFalse();
});

test('markProcessing updates status', function (): void {
    $media = PostMedia::factory()->pending()->create();

    $media->markProcessing();

    expect($media->processing_status)->toBe(MediaProcessingStatus::PROCESSING);
});

test('markCompleted updates status and urls', function (): void {
    $media = PostMedia::factory()->processing()->create();
    $cdnUrl = 'https://cdn.example.com/media/123.jpg';
    $thumbnailUrl = 'https://cdn.example.com/media/thumb_123.jpg';

    $media->markCompleted($cdnUrl, $thumbnailUrl);

    expect($media->processing_status)->toBe(MediaProcessingStatus::COMPLETED)
        ->and($media->cdn_url)->toBe($cdnUrl)
        ->and($media->thumbnail_url)->toBe($thumbnailUrl);
});

test('markCompleted without urls only updates status', function (): void {
    $media = PostMedia::factory()->processing()->create([
        'cdn_url' => null,
        'thumbnail_url' => null,
    ]);

    $media->markCompleted();

    expect($media->processing_status)->toBe(MediaProcessingStatus::COMPLETED)
        ->and($media->cdn_url)->toBeNull()
        ->and($media->thumbnail_url)->toBeNull();
});

test('markFailed updates status', function (): void {
    $media = PostMedia::factory()->processing()->create();

    $media->markFailed();

    expect($media->processing_status)->toBe(MediaProcessingStatus::FAILED);
});

test('getUrl returns cdn_url when available', function (): void {
    $media = PostMedia::factory()->create([
        'cdn_url' => 'https://cdn.example.com/media/123.jpg',
        'storage_path' => '/storage/media/123.jpg',
    ]);

    expect($media->getUrl())->toBe('https://cdn.example.com/media/123.jpg');
});

test('getUrl returns storage_path when cdn_url not available', function (): void {
    $media = PostMedia::factory()->create([
        'cdn_url' => null,
        'storage_path' => '/storage/media/123.jpg',
    ]);

    expect($media->getUrl())->toBe('/storage/media/123.jpg');
});

test('getDimensions returns width and height', function (): void {
    $media = PostMedia::factory()->create([
        'dimensions' => ['width' => 1920, 'height' => 1080],
    ]);

    $dimensions = $media->getDimensions();

    expect($dimensions)->toBeArray()
        ->and($dimensions['width'])->toBe(1920)
        ->and($dimensions['height'])->toBe(1080);
});

test('getDimensions returns nulls when dimensions not set', function (): void {
    $media = PostMedia::factory()->create(['dimensions' => null]);

    $dimensions = $media->getDimensions();

    expect($dimensions)->toBeArray()
        ->and($dimensions['width'])->toBeNull()
        ->and($dimensions['height'])->toBeNull();
});

test('factory creates valid model', function (): void {
    $media = PostMedia::factory()->create();

    expect($media)->toBeInstanceOf(PostMedia::class)
        ->and($media->id)->not->toBeNull()
        ->and($media->post_id)->not->toBeNull()
        ->and($media->type)->toBeInstanceOf(MediaType::class)
        ->and($media->file_name)->not->toBeNull()
        ->and($media->file_size)->toBeGreaterThan(0)
        ->and($media->mime_type)->not->toBeNull()
        ->and($media->storage_path)->not->toBeNull()
        ->and($media->processing_status)->toBeInstanceOf(MediaProcessingStatus::class);
});

test('factory image state works correctly', function (): void {
    $media = PostMedia::factory()->image()->create();

    expect($media->type)->toBe(MediaType::IMAGE)
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->duration_seconds)->toBeNull();
});

test('factory video state works correctly', function (): void {
    $media = PostMedia::factory()->video()->create();

    expect($media->type)->toBe(MediaType::VIDEO)
        ->and($media->mime_type)->toBe('video/mp4')
        ->and($media->duration_seconds)->toBeGreaterThan(0);
});

test('factory gif state works correctly', function (): void {
    $media = PostMedia::factory()->gif()->create();

    expect($media->type)->toBe(MediaType::GIF)
        ->and($media->mime_type)->toBe('image/gif');
});

test('factory document state works correctly', function (): void {
    $media = PostMedia::factory()->document()->create();

    expect($media->type)->toBe(MediaType::DOCUMENT)
        ->and($media->mime_type)->toBe('application/pdf')
        ->and($media->dimensions)->toBeNull();
});

test('factory pending state works correctly', function (): void {
    $media = PostMedia::factory()->pending()->create();

    expect($media->processing_status)->toBe(MediaProcessingStatus::PENDING)
        ->and($media->cdn_url)->toBeNull()
        ->and($media->thumbnail_url)->toBeNull();
});

test('factory processing state works correctly', function (): void {
    $media = PostMedia::factory()->processing()->create();

    expect($media->processing_status)->toBe(MediaProcessingStatus::PROCESSING);
});

test('factory completed state works correctly', function (): void {
    $media = PostMedia::factory()->completed()->create();

    expect($media->processing_status)->toBe(MediaProcessingStatus::COMPLETED)
        ->and($media->cdn_url)->not->toBeNull()
        ->and($media->thumbnail_url)->not->toBeNull();
});

test('factory processingFailed state works correctly', function (): void {
    $media = PostMedia::factory()->processingFailed()->create();

    expect($media->processing_status)->toBe(MediaProcessingStatus::FAILED);
});

test('factory withSortOrder state works correctly', function (): void {
    $media = PostMedia::factory()->withSortOrder(5)->create();

    expect($media->sort_order)->toBe(5);
});
