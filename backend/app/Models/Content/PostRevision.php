<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PostRevision Model
 *
 * Represents a historical revision of a post's content.
 *
 * @property string $id UUID primary key
 * @property string $post_id Post UUID
 * @property string $user_id User UUID
 * @property string|null $content_text Post content text at this revision
 * @property array|null $content_variations Platform-specific content at this revision
 * @property array|null $hashtags Hashtags at this revision
 * @property int $revision_number Sequential revision number
 * @property string|null $change_summary Summary of changes
 * @property \Carbon\Carbon $created_at
 *
 * @property-read Post $post
 * @property-read User $user
 */
final class PostRevision extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_revisions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'content_text',
        'content_variations',
        'hashtags',
        'revision_number',
        'change_summary',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content_variations' => 'array',
            'hashtags' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the post this revision belongs to.
     *
     * @return BelongsTo<Post, PostRevision>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who created this revision.
     *
     * @return BelongsTo<User, PostRevision>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
