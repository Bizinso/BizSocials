<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Services\BaseService;
use Closure;
use Intervention\Image\Laravel\Facades\Image;

final class ImageEditorService extends BaseService
{
    /**
     * Crop an image to the specified dimensions.
     */
    public function crop(string $inputPath, int $x, int $y, int $width, int $height): string
    {
        return $this->processImage($inputPath, function ($image) use ($x, $y, $width, $height) {
            return $image->crop($width, $height, $x, $y);
        });
    }

    /**
     * Resize an image to the specified dimensions.
     */
    public function resize(string $inputPath, int $width, ?int $height = null): string
    {
        return $this->processImage($inputPath, function ($image) use ($width, $height) {
            if ($height !== null) {
                return $image->resize($width, $height);
            }

            return $image->scale(width: $width);
        });
    }

    /**
     * Add text overlay to an image.
     */
    public function addText(string $inputPath, string $text, int $x, int $y, int $size, string $color): string
    {
        return $this->processImage($inputPath, function ($image) use ($text, $x, $y, $size, $color) {
            return $image->text($text, $x, $y, function ($font) use ($size, $color) {
                $font->size($size);
                $font->color($color);
            });
        });
    }

    /**
     * Rotate an image by the specified angle.
     */
    public function rotate(string $inputPath, float $angle): string
    {
        return $this->processImage($inputPath, function ($image) use ($angle) {
            return $image->rotate($angle);
        });
    }

    /**
     * Flip an image horizontally or vertically.
     */
    public function flip(string $inputPath, string $direction): string
    {
        return $this->processImage($inputPath, function ($image) use ($direction) {
            if ($direction === 'h') {
                return $image->flip();
            }

            return $image->flop();
        });
    }

    /**
     * Apply a filter to an image.
     */
    public function applyFilter(string $inputPath, string $filter): string
    {
        return $this->processImage($inputPath, function ($image) use ($filter) {
            return match ($filter) {
                'grayscale' => $image->greyscale(),
                'sepia' => $image->greyscale()->brightness(10)->colorize(20, 10, -10),
                'blur' => $image->blur(15),
                'sharpen' => $image->sharpen(15),
                'brightness' => $image->brightness(20),
                'contrast' => $image->contrast(20),
                default => $image,
            };
        });
    }

    /**
     * Process an image with the given callback.
     * Opens the image, applies the callback, saves to a new temp path, and returns the path.
     */
    private function processImage(string $inputPath, Closure $callback): string
    {
        $extension = pathinfo($inputPath, PATHINFO_EXTENSION) ?: 'png';
        $outputPath = sys_get_temp_dir() . '/' . uniqid('img_', true) . '.' . $extension;

        $image = Image::read($inputPath);
        $image = $callback($image);
        $image->save($outputPath);

        $this->log('Image processed', [
            'input' => basename($inputPath),
            'output' => basename($outputPath),
        ]);

        return $outputPath;
    }
}
