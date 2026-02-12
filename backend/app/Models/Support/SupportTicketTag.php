<?php

declare(strict_types=1);

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * SupportTicketTag Model
 *
 * Represents a tag for categorizing support tickets.
 *
 * @property string $id UUID primary key
 * @property string $name Tag name
 * @property string $slug URL-friendly slug
 * @property string $color Hex color code
 * @property string|null $description Tag description
 * @property int $usage_count Number of times the tag is used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Collection<int, SupportTicket> $tickets
 *
 * @method static Builder<static> popular()
 * @method static Builder<static> ordered()
 * @method static Builder<static> search(string $query)
 */
final class SupportTicketTag extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_ticket_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'usage_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get the tickets with this tag.
     *
     * @return BelongsToMany<SupportTicket>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(
            SupportTicket::class,
            'support_ticket_tag_assignments',
            'tag_id',
            'ticket_id'
        )->withTimestamps();
    }

    /**
     * Scope to order by usage count (most popular first).
     *
     * @param  Builder<SupportTicketTag>  $query
     * @return Builder<SupportTicketTag>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('usage_count');
    }

    /**
     * Scope to order by name.
     *
     * @param  Builder<SupportTicketTag>  $query
     * @return Builder<SupportTicketTag>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to search tags by name.
     *
     * @param  Builder<SupportTicketTag>  $query
     * @return Builder<SupportTicketTag>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where('name', 'like', "%{$searchQuery}%")
            ->orWhere('slug', 'like', "%{$searchQuery}%");
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsageCount(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement the usage count.
     */
    public function decrementUsageCount(): void
    {
        $this->decrement('usage_count');
    }
}
