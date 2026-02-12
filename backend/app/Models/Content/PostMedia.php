<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\Content\MediaProcessingStatus;
use App\Enums\Content\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PostMedia Model
 *
 * Represents a media attachment for a post.
 * Supports images, videos, GIFs, and documents.
 *
 * @property string $id UUID primary key
 * @property string $post_id Post UUID
 * @property MediaType $type Media type
 * @property string $file_name Original file name
 * @property int $file_size File size in bytes
 * @property string $mime_type MIME type
 * @property string $storage_path Storage path
 * @property string|null $cdn_url CDN URL
 * @property string|null $thumbnail_url Thumbnail URL
 * @property array|null $dimensions Width and height
 * @property int|null $duration_seconds Duration for videos
 * @property string|null $alt_text Accessibility text
 * @property int $sort_order Display order
 * @property MediaProcessingStatus $processing_status Processing status
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Post $post
 *
 * @method static Builder<static> forPost(string $postId)
 * @method static Builder<static> images()
 * @method static Builder<static> videos()
 * @method static Builder<static> ready()
 */
final class PostMedia extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_media';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'type',
        'file_name',
        'file_size',
        'mime_type',
        'storage_path',
        'cdn_url',
        'thumbnail_url',
        'dimensions',
        'duration_seconds',
        'alt_text',
        'sort_order',
        'processing_status',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'processing_status' => MediaProcessingStatus::class,
            'dimensions' => 'array',
            'metadata' => 'array',
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the post this media belongs to.
     *
     * @return BelongsTo<Post, PostMedia>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope to filter by post.
     *
     * @param  Builder<PostMedia>  $query
     * @return Builder<PostMedia>
     */
    public function scopeForPost(Builder $query, string $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope to get only images.
     *
     * @param  Builder<PostMedia>  $query
     * @return Builder<PostMedia>
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', MediaType::IMAGE);
    }

    /**
     * Scope to get only videos.
     *
     * @param  Builder<PostMedia>  $query
     * @return Builder<PostMedia>
     */
    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', MediaType::VIDEO);
    }

    /**
     * Scope to get only ready media.
     *
     * @param  Builder<PostMedia>  $query
     * @return Builder<PostMedia>
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('processing_status', MediaProcessingStatus::COMPLETED);
    }

    /**
     * Check if the media is ready for use.
     */
    public function isReady(): bool
    {
        return $this->processing_status->isReady();
    }

    /**
     * Check if the media is currently processing.
     */
    public function isProcessing(): bool
    {
        return $this->processing_status === MediaProcessingStatus::PROCESSING;
    }

    /**
     * Check if the media processing has failed.
     */
    public function hasFailed(): bool
    {
        return $this->processing_status === MediaProcessingStatus::FAILED;
    }

    /**
     * Mark the media as processing.
     */
    public function markProcessing(): void
    {
        $this->processing_status = MediaProcessingStatus::PROCESSING;
        $this->save();
    }

    /**
     * Mark the media as completed.
     */
    public function markCompleted(?string $cdnUrl = null, ?string $thumbnailUrl = null): void
    {
        $this->processing_status = MediaProcessingStatus::COMPLETED;

        if ($cdnUrl !== null) {
            $this->cdn_url = $cdnUrl;
        }

        if ($thumbnailUrl !== null) {
            $this->thumbnail_url = $thumbnailUrl;
        }

        $this->save();
    }

    /**
     * Mark the media as failed.
     */
    public function markFailed(): void
    {
        $this->processing_status = MediaProcessingStatus::FAILED;
        $this->save();
    }

    /**
     * Get the URL for the media.
     * Returns CDN URL if available, otherwise storage path.
     */
    public function getUrl(): string
    {
        return $this->cdn_url ?? $this->storage_path;
    }

    /**
     * Get the dimensions as an array.
     *
     * @return array{width: int|null, height: int|null}
     */
    public function getDimensions(): array
    {
        return [
            'width' => $this->dimensions['width'] ?? null,
            'height' => $this->dimensions['height'] ?? null,
        ];
    }
}
