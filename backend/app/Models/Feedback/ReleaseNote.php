<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Enums\Feedback\ChangeType;
use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ReleaseNote Model
 *
 * Represents a release note entry.
 *
 * @property string $id UUID primary key
 * @property string $version Version number (e.g., 1.2.3)
 * @property string|null $version_name Version name (e.g., "Phoenix")
 * @property string $title Release title
 * @property string|null $summary Short summary
 * @property string $content Full content
 * @property string $content_format Content format (markdown, html)
 * @property ReleaseType $release_type Type of release
 * @property ReleaseNoteStatus $status Publication status
 * @property bool $is_public Publicly visible
 * @property \Carbon\Carbon|null $scheduled_at Scheduled publication date
 * @property \Carbon\Carbon|null $published_at Publication date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Collection<int, ReleaseNoteItem> $items
 *
 * @method static Builder<static> published()
 * @method static Builder<static> draft()
 * @method static Builder<static> scheduled()
 * @method static Builder<static> byType(ReleaseType $type)
 * @method static Builder<static> recent()
 */
final class ReleaseNote extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'release_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'version',
        'version_name',
        'title',
        'summary',
        'content',
        'content_format',
        'release_type',
        'status',
        'is_public',
        'scheduled_at',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'release_type' => ReleaseType::class,
            'status' => ReleaseNoteStatus::class,
            'is_public' => 'boolean',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get release note items.
     *
     * @return HasMany<ReleaseNoteItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReleaseNoteItem::class, 'release_note_id')->orderBy('sort_order');
    }

    /**
     * Scope to get published release notes.
     *
     * @param  Builder<ReleaseNote>  $query
     * @return Builder<ReleaseNote>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ReleaseNoteStatus::PUBLISHED);
    }

    /**
     * Scope to get draft release notes.
     *
     * @param  Builder<ReleaseNote>  $query
     * @return Builder<ReleaseNote>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', ReleaseNoteStatus::DRAFT);
    }

    /**
     * Scope to get scheduled release notes.
     *
     * @param  Builder<ReleaseNote>  $query
     * @return Builder<ReleaseNote>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', ReleaseNoteStatus::SCHEDULED);
    }

    /**
     * Scope to filter by release type.
     *
     * @param  Builder<ReleaseNote>  $query
     * @return Builder<ReleaseNote>
     */
    public function scopeByType(Builder $query, ReleaseType $type): Builder
    {
        return $query->where('release_type', $type);
    }

    /**
     * Scope to order by published date descending.
     *
     * @param  Builder<ReleaseNote>  $query
     * @return Builder<ReleaseNote>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    /**
     * Check if the release note is published.
     */
    public function isPublished(): bool
    {
        return $this->status === ReleaseNoteStatus::PUBLISHED;
    }

    /**
     * Check if the release note is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === ReleaseNoteStatus::DRAFT;
    }

    /**
     * Check if the release note is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === ReleaseNoteStatus::SCHEDULED;
    }

    /**
     * Publish the release note.
     */
    public function publish(): void
    {
        $this->status = ReleaseNoteStatus::PUBLISHED;
        $this->published_at = now();
        $this->scheduled_at = null;
        $this->save();
    }

    /**
     * Schedule the release note for publication.
     */
    public function schedule(\DateTimeInterface $date): void
    {
        $this->status = ReleaseNoteStatus::SCHEDULED;
        $this->scheduled_at = $date;
        $this->save();
    }

    /**
     * Add an item to the release note.
     */
    public function addItem(string $title, ChangeType $changeType, ?string $description = null, ?string $roadmapItemId = null): ReleaseNoteItem
    {
        $maxOrder = $this->items()->max('sort_order') ?? 0;

        return $this->items()->create([
            'title' => $title,
            'description' => $description,
            'change_type' => $changeType,
            'roadmap_item_id' => $roadmapItemId,
            'sort_order' => $maxOrder + 1,
        ]);
    }
}
