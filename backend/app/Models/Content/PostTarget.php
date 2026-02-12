<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\Content\PostTargetStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PostTarget Model
 *
 * Represents a post being published to a specific social account.
 * Tracks the publishing status, results, and metrics for each target.
 *
 * @property string $id UUID primary key
 * @property string $post_id Post UUID
 * @property string $social_account_id Social account UUID
 * @property string $platform_code Platform code (denormalized)
 * @property string|null $content_override Platform-specific content override
 * @property PostTargetStatus $status Publishing status
 * @property string|null $external_post_id External platform post ID
 * @property string|null $external_post_url External platform post URL
 * @property \Carbon\Carbon|null $published_at When published to this platform
 * @property string|null $error_code Error code if failed
 * @property string|null $error_message Error message if failed
 * @property int $retry_count Number of retry attempts
 * @property array|null $metrics Engagement metrics
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Post $post
 * @property-read SocialAccount $socialAccount
 *
 * @method static Builder<static> forPost(string $postId)
 * @method static Builder<static> forPlatform(SocialPlatform|string $platform)
 * @method static Builder<static> pending()
 * @method static Builder<static> published()
 * @method static Builder<static> failed()
 */
final class PostTarget extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_targets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'social_account_id',
        'platform_code',
        'content_override',
        'status',
        'external_post_id',
        'external_post_url',
        'published_at',
        'error_code',
        'error_message',
        'retry_count',
        'metrics',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostTargetStatus::class,
            'published_at' => 'datetime',
            'metrics' => 'array',
            'retry_count' => 'integer',
        ];
    }

    /**
     * Get the post this target belongs to.
     *
     * @return BelongsTo<Post, PostTarget>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the social account this target publishes to.
     *
     * @return BelongsTo<SocialAccount, PostTarget>
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Scope to filter by post.
     *
     * @param  Builder<PostTarget>  $query
     * @return Builder<PostTarget>
     */
    public function scopeForPost(Builder $query, string $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope to filter by platform.
     *
     * @param  Builder<PostTarget>  $query
     * @param  SocialPlatform|string  $platform
     * @return Builder<PostTarget>
     */
    public function scopeForPlatform(Builder $query, SocialPlatform|string $platform): Builder
    {
        $platformCode = $platform instanceof SocialPlatform ? $platform->value : $platform;

        return $query->where('platform_code', $platformCode);
    }

    /**
     * Scope to get pending targets.
     *
     * @param  Builder<PostTarget>  $query
     * @return Builder<PostTarget>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PostTargetStatus::PENDING);
    }

    /**
     * Scope to get published targets.
     *
     * @param  Builder<PostTarget>  $query
     * @return Builder<PostTarget>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostTargetStatus::PUBLISHED);
    }

    /**
     * Scope to get failed targets.
     *
     * @param  Builder<PostTarget>  $query
     * @return Builder<PostTarget>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PostTargetStatus::FAILED);
    }

    /**
     * Check if the target is published.
     */
    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    /**
     * Check if the target has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status->hasFailed();
    }

    /**
     * Mark the target as publishing.
     */
    public function markPublishing(): void
    {
        $this->status = PostTargetStatus::PUBLISHING;
        $this->save();
    }

    /**
     * Mark the target as published.
     */
    public function markPublished(string $externalPostId, ?string $externalPostUrl = null): void
    {
        $this->status = PostTargetStatus::PUBLISHED;
        $this->external_post_id = $externalPostId;
        $this->external_post_url = $externalPostUrl;
        $this->published_at = now();
        $this->error_code = null;
        $this->error_message = null;
        $this->save();
    }

    /**
     * Mark the target as failed.
     */
    public function markFailed(string $errorCode, string $errorMessage): void
    {
        $this->status = PostTargetStatus::FAILED;
        $this->error_code = $errorCode;
        $this->error_message = $errorMessage;
        $this->save();
    }

    /**
     * Increment the retry count.
     */
    public function incrementRetry(): void
    {
        $this->retry_count++;
        $this->save();
    }

    /**
     * Get the effective content for this target.
     * Returns content_override if set, otherwise falls back to post content.
     */
    public function getContent(): ?string
    {
        if ($this->content_override !== null) {
            return $this->content_override;
        }

        return $this->post?->content_text;
    }
}
