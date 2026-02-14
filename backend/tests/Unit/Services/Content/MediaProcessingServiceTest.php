<?php

declare(strict_types=1);

use App\Enums\Content\MediaProcessingStatus;
use App\Enums\Content\MediaType;
use App\Models\Content\PostMedia;
use App\Services\Content\MediaProcessingService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(MediaProcessingService::class);
    Storage::fake('s3');
});

describe('MediaProcessingService', function () {
    describe('processImage', function () {
        it('processes image and generates variants', function () {
            // Create a simple test image
            $imageContent = file_get_contents(__DIR__ . '/../../../Fixtures/test-image.jpg');
            $path = 'media/test/image.jpg';
            Storage::disk('s3')->put($path, $imageContent);

            $media = PostMedia::factory()->create([
                'type' => MediaType::IMAGE,
                'storage_path' => $path,
                'processing_status' => MediaProcessingStatus::PENDING,
            ]);

            $this->service->processImage($media);

            // Check that variants were created
            expect($media->fresh()->metadata)->toHaveKey('variants');
            expect($media->fresh()->thumbnail_url)->not->toBeNull();
            expect($media->fresh()->processing_status)->toBe(MediaProcessingStatus::COMPLETED);
        });

        it('stores image dimensions', function () {
            $imageContent = file_get_contents(__DIR__ . '/../../../Fixtures/test-image.jpg');
            $path = 'media/test/image.jpg';
            Storage::disk('s3')->put($path, $imageContent);

            $media = PostMedia::factory()->create([
                'type' => MediaType::IMAGE,
                'storage_path' => $path,
            ]);

            $this->service->processImage($media);

            expect($media->fresh()->dimensions)->toHaveKey('width');
            expect($media->fresh()->dimensions)->toHaveKey('height');
        });
    });

    describe('processGif', function () {
        it('generates thumbnail for GIF', function () {
            // Create a simple test GIF (we'll use a static image for testing)
            $imageContent = file_get_contents(__DIR__ . '/../../../Fixtures/test-image.jpg');
            $path = 'media/test/animation.gif';
            Storage::disk('s3')->put($path, $imageContent);

            $media = PostMedia::factory()->create([
                'type' => MediaType::GIF,
                'storage_path' => $path,
            ]);

            $this->service->processGif($media);

            expect($media->fresh()->thumbnail_url)->not->toBeNull();
            expect($media->fresh()->processing_status)->toBe(MediaProcessingStatus::COMPLETED);
        });
    });

    describe('processDocument', function () {
        it('marks document as completed without processing', function () {
            $media = PostMedia::factory()->create([
                'type' => MediaType::DOCUMENT,
                'storage_path' => 'media/test/document.pdf',
            ]);

            $this->service->processDocument($media);

            expect($media->fresh()->processing_status)->toBe(MediaProcessingStatus::COMPLETED);
        });
    });

    describe('process', function () {
        it('routes to correct processor based on media type', function () {
            $imageContent = file_get_contents(__DIR__ . '/../../../Fixtures/test-image.jpg');
            $path = 'media/test/image.jpg';
            Storage::disk('s3')->put($path, $imageContent);

            $imageMedia = PostMedia::factory()->create([
                'type' => MediaType::IMAGE,
                'storage_path' => $path,
            ]);

            $this->service->process($imageMedia);

            expect($imageMedia->fresh()->processing_status)->toBe(MediaProcessingStatus::COMPLETED);
        });
    });
});
