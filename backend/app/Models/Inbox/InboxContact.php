<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InboxContact Model
 *
 * Represents a social CRM contact — a person who has interacted
 * with the workspace through social platforms.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $platform Social platform name
 * @property string $platform_user_id Platform-specific user ID
 * @property string $display_name Display name
 * @property string|null $username Username on the platform
 * @property string|null $avatar_url Avatar URL
 * @property string|null $email Contact email
 * @property string|null $phone Contact phone
 * @property string|null $notes Internal notes about the contact
 * @property array|null $tags Tags for categorization
 * @property \Carbon\Carbon $first_seen_at When first interacted
 * @property \Carbon\Carbon $last_seen_at When last interacted
 * @property int $interaction_count Number of interactions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forPlatform(string $platform)
 * @method static Builder<static> search(string $term)
 */
final class InboxContact extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'platform',
        'platform_user_id',
        'display_name',
        'username',
        'avatar_url',
        'email',
        'phone',
        'notes',
        'tags',
        'first_seen_at',
        'last_seen_at',
        'interaction_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'interaction_count' => 'integer',
        ];
    }

    /**
     * Get the workspace that this contact belongs to.
     *
     * @return BelongsTo<Workspace, InboxContact>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<InboxContact>  $query
     * @return Builder<InboxContact>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by platform.
     *
     * @param  Builder<InboxContact>  $query
     * @return Builder<InboxContact>
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to search by display name, username, or email.
     *
     * @param  Builder<InboxContact>  $query
     * @return Builder<InboxContact>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term): void {
            $q->where('display_name', 'like', '%' . $term . '%')
                ->orWhere('username', 'like', '%' . $term . '%')
                ->orWhere('email', 'like', '%' . $term . '%');
        });
    }

    /**
     * Increment the interaction count and update last_seen_at.
     */
    public function incrementInteraction(): void
    {
        $this->increment('interaction_count');
        $this->update(['last_seen_at' => now()]);
    }
}
