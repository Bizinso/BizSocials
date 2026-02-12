<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\WatermarkPreset;
use App\Services\BaseService;
use Intervention\Image\Laravel\Facades\Image;

final class WatermarkService extends BaseService
{
    /**
     * Apply a watermark preset to an image.
     */
    public function apply(string $imagePath, WatermarkPreset $preset): string
    {
        if ($preset->type === 'text') {
            return $this->applyTextWatermark(
                $imagePath,
                $preset->text ?? '',
                $preset->position,
                $preset->opacity,
                $preset->scale,
            );
        }

        return $this->applyImageWatermark(
            $imagePath,
            $preset->image_path ?? '',
            $preset->position,
            $preset->opacity,
            $preset->scale,
        );
    }

    /**
     * Apply a text watermark to an image.
     */
    public function applyTextWatermark(
        string $imagePath,
        string $text,
        string $position,
        int $opacity,
        int $size,
    ): string {
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'png';
        $outputPath = sys_get_temp_dir() . '/' . uniqid('wm_', true) . '.' . $extension;

        $image = Image::read($imagePath);
        $imgWidth = $image->width();
        $imgHeight = $image->height();

        // Estimate text dimensions based on size
        $textWidth = (int) (strlen($text) * $size * 0.6);
        $textHeight = (int) ($size * 1.2);

        [$x, $y] = $this->calculatePosition($imgWidth, $imgHeight, $textWidth, $textHeight, $position);

        $alphaHex = str_pad(dechex((int) round($opacity * 2.55)), 2, '0', STR_PAD_LEFT);
        $color = $alphaHex . 'ffffff';

        $image->text($text, $x, $y, function ($font) use ($size, $color) {
            $font->size($size);
            $font->color($color);
        });

        $image->save($outputPath);

        $this->log('Text watermark applied', [
            'text' => $text,
            'position' => $position,
        ]);

        return $outputPath;
    }

    /**
     * Apply an image watermark to an image.
     */
    public function applyImageWatermark(
        string $imagePath,
        string $watermarkPath,
        string $position,
        int $opacity,
        int $scale,
    ): string {
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'png';
        $outputPath = sys_get_temp_dir() . '/' . uniqid('wm_', true) . '.' . $extension;

        $image = Image::read($imagePath);
        $watermark = Image::read($watermarkPath);

        $imgWidth = $image->width();
        $imgHeight = $image->height();

        // Scale watermark relative to the main image
        $wmTargetWidth = (int) ($imgWidth * $scale / 100);
        $watermark->scale(width: $wmTargetWidth);

        $wmWidth = $watermark->width();
        $wmHeight = $watermark->height();

        [$x, $y] = $this->calculatePosition($imgWidth, $imgHeight, $wmWidth, $wmHeight, $position);

        // Apply opacity to watermark
        $watermark->brightness((int) (-100 + $opacity));

        $image->place($watermark, 'top-left', $x, $y);
        $image->save($outputPath);

        $this->log('Image watermark applied', [
            'position' => $position,
            'scale' => $scale,
        ]);

        return $outputPath;
    }

    /**
     * Calculate the position for the watermark based on the position string.
     *
     * @return array{0: int, 1: int}
     */
    private function calculatePosition(
        int $imgWidth,
        int $imgHeight,
        int $wmWidth,
        int $wmHeight,
        string $position,
    ): array {
        $padding = 20;

        return match ($position) {
            'top-left' => [$padding, $padding],
            'top-center' => [(int) (($imgWidth - $wmWidth) / 2), $padding],
            'top-right' => [$imgWidth - $wmWidth - $padding, $padding],
            'center-left' => [$padding, (int) (($imgHeight - $wmHeight) / 2)],
            'center' => [(int) (($imgWidth - $wmWidth) / 2), (int) (($imgHeight - $wmHeight) / 2)],
            'center-right' => [$imgWidth - $wmWidth - $padding, (int) (($imgHeight - $wmHeight) / 2)],
            'bottom-left' => [$padding, $imgHeight - $wmHeight - $padding],
            'bottom-center' => [(int) (($imgWidth - $wmWidth) / 2), $imgHeight - $wmHeight - $padding],
            'bottom-right' => [$imgWidth - $wmWidth - $padding, $imgHeight - $wmHeight - $padding],
            default => [$imgWidth - $wmWidth - $padding, $imgHeight - $wmHeight - $padding],
        };
    }
}
