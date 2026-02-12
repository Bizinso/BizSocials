<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\Content\MediaType;
use App\Models\Content\PostMedia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

final class MediaProcessingService
{
    private const SOCIAL_CARD_WIDTH = 1200;
    private const SOCIAL_CARD_HEIGHT = 630;
    private const INSTAGRAM_SQUARE = 1080;
    private const THUMBNAIL_SIZE = 200;
    private const MAX_IMAGE_BYTES = 500 * 1024; // 500 KB

    /**
     * Process a media item based on its type.
     */
    public function process(PostMedia $media): void
    {
        match ($media->type) {
            MediaType::IMAGE => $this->processImage($media),
            MediaType::VIDEO => $this->processVideo($media),
            MediaType::GIF => $this->processGif($media),
            MediaType::DOCUMENT => $this->processDocument($media),
        };
    }

    /**
     * Process an image: resize, compress, generate thumbnail.
     */
    public function processImage(PostMedia $media): void
    {
        $disk = Storage::disk('s3');
        $contents = $disk->get($media->storage_path);

        if ($contents === null) {
            throw new \RuntimeException("Cannot read file: {$media->storage_path}");
        }

        $image = Image::read($contents);
        $width = $image->width();
        $height = $image->height();

        // Store original dimensions
        $media->dimensions = ['width' => $width, 'height' => $height];

        // Generate social card variant (1200x630) — cover crop
        $socialCard = Image::read($contents)
            ->cover(self::SOCIAL_CARD_WIDTH, self::SOCIAL_CARD_HEIGHT)
            ->toJpeg(quality: 80);

        $socialPath = $this->variantPath($media->storage_path, 'social');
        $disk->put($socialPath, (string) $socialCard);

        // Generate Instagram square variant (1080x1080) — cover crop
        $square = Image::read($contents)
            ->cover(self::INSTAGRAM_SQUARE, self::INSTAGRAM_SQUARE)
            ->toJpeg(quality: 80);

        $squarePath = $this->variantPath($media->storage_path, 'square');
        $disk->put($squarePath, (string) $square);

        // Compress original if too large
        if ($media->file_size > self::MAX_IMAGE_BYTES) {
            $compressed = Image::read($contents)
                ->scaleDown(width: 1920)
                ->toJpeg(quality: 75);

            $disk->put($media->storage_path, (string) $compressed);
            $media->file_size = strlen((string) $compressed);
        }

        // Generate thumbnail (200x200)
        $thumbnailUrl = $this->generateThumbnail($contents, $media->storage_path);
        $media->thumbnail_url = $thumbnailUrl;

        // Store variant paths in metadata
        $media->metadata = array_merge($media->metadata ?? [], [
            'variants' => [
                'social' => $socialPath,
                'square' => $squarePath,
            ],
        ]);

        $media->markCompleted(null, $thumbnailUrl);
    }

    /**
     * Process a video: extract thumbnail via FFmpeg.
     */
    public function processVideo(PostMedia $media): void
    {
        $disk = Storage::disk('s3');

        // Download video to temp file for FFmpeg processing
        $tempDir = sys_get_temp_dir();
        $tempVideo = $tempDir . '/' . uniqid('video_') . '.mp4';
        $tempThumb = $tempDir . '/' . uniqid('thumb_') . '.jpg';

        try {
            $contents = $disk->get($media->storage_path);
            if ($contents === null) {
                throw new \RuntimeException("Cannot read file: {$media->storage_path}");
            }

            file_put_contents($tempVideo, $contents);

            // Extract thumbnail at 1 second mark using FFmpeg
            $cmd = sprintf(
                'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf scale=400:-1 %s -y 2>&1',
                escapeshellarg($tempVideo),
                escapeshellarg($tempThumb)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($tempThumb)) {
                $thumbnailPath = $this->variantPath($media->storage_path, 'thumb');
                $disk->put($thumbnailPath, file_get_contents($tempThumb));
                $media->thumbnail_url = $thumbnailPath;

                // Get video duration
                $durationCmd = sprintf(
                    'ffprobe -v quiet -show_entries format=duration -of csv=p=0 %s',
                    escapeshellarg($tempVideo)
                );
                $duration = trim((string) shell_exec($durationCmd));
                if (is_numeric($duration)) {
                    $media->duration_seconds = (int) round((float) $duration);
                }
            } else {
                Log::warning('FFmpeg thumbnail extraction failed', [
                    'media_id' => $media->id,
                    'exit_code' => $exitCode,
                    'output' => implode("\n", $output),
                ]);
            }

            $media->markCompleted(null, $media->thumbnail_url);
        } finally {
            @unlink($tempVideo);
            @unlink($tempThumb);
        }
    }

    /**
     * Process a GIF: generate a still thumbnail.
     */
    public function processGif(PostMedia $media): void
    {
        $disk = Storage::disk('s3');
        $contents = $disk->get($media->storage_path);

        if ($contents === null) {
            throw new \RuntimeException("Cannot read file: {$media->storage_path}");
        }

        // Extract first frame as thumbnail
        $thumbnailUrl = $this->generateThumbnail($contents, $media->storage_path);
        $media->thumbnail_url = $thumbnailUrl;
        $media->markCompleted(null, $thumbnailUrl);
    }

    /**
     * Process a document: no image processing needed.
     */
    public function processDocument(PostMedia $media): void
    {
        $media->markCompleted();
    }

    /**
     * Generate a 200x200 thumbnail and store it.
     */
    private function generateThumbnail(string $imageContents, string $originalPath): string
    {
        $thumbnail = Image::read($imageContents)
            ->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE)
            ->toJpeg(quality: 70);

        $thumbPath = $this->variantPath($originalPath, 'thumb');
        Storage::disk('s3')->put($thumbPath, (string) $thumbnail);

        return $thumbPath;
    }

    /**
     * Generate a variant path from the original storage path.
     */
    private function variantPath(string $originalPath, string $variant): string
    {
        $info = pathinfo($originalPath);
        $dir = $info['dirname'] ?? '';
        $name = $info['filename'] ?? 'file';

        return "{$dir}/{$name}_{$variant}.jpg";
    }
}
