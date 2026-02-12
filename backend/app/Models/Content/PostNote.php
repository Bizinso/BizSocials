<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PostNote Model
 *
 * Represents a note/comment attached to a post for team collaboration.
 *
 * @property string $id UUID primary key
 * @property string $post_id Post UUID
 * @property string $user_id User UUID
 * @property string $content Note content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Post $post
 * @property-read User $user
 */
final class PostNote extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
    ];

    /**
     * Get the post this note belongs to.
     *
     * @return BelongsTo<Post, PostNote>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who wrote this note.
     *
     * @return BelongsTo<User, PostNote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
