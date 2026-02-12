<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ShortLink Model
 *
 * Represents a shortened URL with UTM tracking.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $original_url Original URL
 * @property string $short_code Auto-generated short code
 * @property string|null $custom_alias Custom alias
 * @property string|null $title Link title
 * @property int $click_count Number of clicks
 * @property string|null $utm_source UTM source parameter
 * @property string|null $utm_medium UTM medium parameter
 * @property string|null $utm_campaign UTM campaign parameter
 * @property string|null $utm_term UTM term parameter
 * @property string|null $utm_content UTM content parameter
 * @property \Carbon\Carbon|null $expires_at Expiration timestamp
 * @property string $created_by_user_id User who created the link
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read User $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShortLinkClick> $clicks
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 */
final class ShortLink extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'short_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'original_url',
        'short_code',
        'custom_alias',
        'title',
        'click_count',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'expires_at',
        'created_by_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'click_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace that this link belongs to.
     *
     * @return BelongsTo<Workspace, ShortLink>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who created this link.
     *
     * @return BelongsTo<User, ShortLink>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the clicks for this link.
     *
     * @return HasMany<ShortLinkClick>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(ShortLinkClick::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<ShortLink>  $query
     * @return Builder<ShortLink>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Check if the link has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get the full short URL.
     */
    public function getFullUrl(): string
    {
        $baseUrl = config('app.url');
        $code = $this->custom_alias ?? $this->short_code;

        return $baseUrl . '/s/' . $code;
    }

    /**
     * Build the original URL with UTM parameters.
     */
    public function buildUtmUrl(): string
    {
        $url = $this->original_url;
        $params = [];

        if ($this->utm_source !== null) {
            $params['utm_source'] = $this->utm_source;
        }
        if ($this->utm_medium !== null) {
            $params['utm_medium'] = $this->utm_medium;
        }
        if ($this->utm_campaign !== null) {
            $params['utm_campaign'] = $this->utm_campaign;
        }
        if ($this->utm_term !== null) {
            $params['utm_term'] = $this->utm_term;
        }
        if ($this->utm_content !== null) {
            $params['utm_content'] = $this->utm_content;
        }

        if (empty($params)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }
}
