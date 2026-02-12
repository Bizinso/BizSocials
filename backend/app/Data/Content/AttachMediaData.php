<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Enums\Content\MediaType;
use Spatie\LaravelData\Data;

final class AttachMediaData extends Data
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public MediaType $media_type,
        public string $file_path,
        public ?string $file_url = null,
        public ?string $thumbnail_url = null,
        public ?string $original_filename = null,
        public ?int $file_size = null,
        public ?string $mime_type = null,
        public int $sort_order = 0,
        public ?array $metadata = null,
    ) {}
}
