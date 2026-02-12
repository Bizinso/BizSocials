<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Models\Content\PostMedia;
use Spatie\LaravelData\Data;

final class PostMediaData extends Data
{
    public function __construct(
        public string $id,
        public string $post_id,
        public string $media_type,
        public string $file_path,
        public ?string $file_url,
        public ?string $thumbnail_url,
        public ?string $original_filename,
        public ?int $file_size,
        public ?string $mime_type,
        public int $sort_order,
        public ?array $metadata,
        public string $processing_status,
    ) {}

    /**
     * Create PostMediaData from a PostMedia model.
     */
    public static function fromModel(PostMedia $media): self
    {
        return new self(
            id: $media->id,
            post_id: $media->post_id,
            media_type: $media->type->value,
            file_path: $media->storage_path,
            file_url: $media->cdn_url ?? $media->storage_path,
            thumbnail_url: $media->thumbnail_url,
            original_filename: $media->file_name,
            file_size: $media->file_size,
            mime_type: $media->mime_type,
            sort_order: $media->sort_order,
            metadata: $media->metadata,
            processing_status: $media->processing_status->value,
        );
    }
}
