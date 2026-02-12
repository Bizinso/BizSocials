<?php

declare(strict_types=1);

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SupportCategory Model
 *
 * Represents a category for organizing support tickets.
 *
 * @property string $id UUID primary key
 * @property string $name Category name
 * @property string $slug URL-friendly slug
 * @property string|null $description Category description
 * @property string $color Hex color code
 * @property string|null $icon Icon name
 * @property string|null $parent_id Parent category UUID
 * @property int $sort_order Sort order for display
 * @property bool $is_active Whether the category is active
 * @property int $ticket_count Number of tickets in this category
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SupportCategory|null $parent
 * @property-read Collection<int, SupportCategory> $children
 * @property-read Collection<int, SupportTicket> $tickets
 *
 * @method static Builder<static> active()
 * @method static Builder<static> roots()
 * @method static Builder<static> ordered()
 * @method static Builder<static> withTicketCount()
 */
final class SupportCategory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
        'ticket_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'ticket_count' => 'integer',
        ];
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<SupportCategory, SupportCategory>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<SupportCategory>
     */
    public function children(): HasMany
    {
        return $this->hasMany(SupportCategory::class, 'parent_id');
    }

    /**
     * Get the tickets in this category.
     *
     * @return HasMany<SupportTicket>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'category_id');
    }

    /**
     * Scope to get only active categories.
     *
     * @param  Builder<SupportCategory>  $query
     * @return Builder<SupportCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only root categories (no parent).
     *
     * @param  Builder<SupportCategory>  $query
     * @return Builder<SupportCategory>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to order by sort_order.
     *
     * @param  Builder<SupportCategory>  $query
     * @return Builder<SupportCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to include ticket count.
     *
     * @param  Builder<SupportCategory>  $query
     * @return Builder<SupportCategory>
     */
    public function scopeWithTicketCount(Builder $query): Builder
    {
        return $query->withCount('tickets');
    }

    /**
     * Check if this is a root category.
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the full path of the category (including parent names).
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current->name);
        }

        return implode(' > ', $path);
    }

    /**
     * Increment the ticket count.
     */
    public function incrementTicketCount(): void
    {
        $this->increment('ticket_count');
    }

    /**
     * Decrement the ticket count.
     */
    public function decrementTicketCount(): void
    {
        $this->decrement('ticket_count');
    }
}
