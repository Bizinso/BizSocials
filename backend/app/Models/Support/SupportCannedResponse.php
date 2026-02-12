<?php

declare(strict_types=1);

namespace App\Models\Support;

use App\Enums\Support\CannedResponseCategory;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupportCannedResponse Model
 *
 * Represents a pre-defined response template for support staff.
 *
 * @property string $id UUID primary key
 * @property string $title Response title
 * @property string|null $shortcut Keyboard shortcut
 * @property string $content Response content
 * @property CannedResponseCategory $category Response category
 * @property string $created_by Creator admin UUID
 * @property bool $is_shared Whether shared with all staff
 * @property int $usage_count Number of times used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SuperAdminUser $creator
 *
 * @method static Builder<static> shared()
 * @method static Builder<static> byCategory(CannedResponseCategory $category)
 * @method static Builder<static> byCreator(string $adminId)
 * @method static Builder<static> search(string $query)
 * @method static Builder<static> popular()
 */
final class SupportCannedResponse extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_canned_responses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'shortcut',
        'content',
        'category',
        'created_by',
        'is_shared',
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
            'category' => CannedResponseCategory::class,
            'is_shared' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get the creator admin.
     *
     * @return BelongsTo<SuperAdminUser, SupportCannedResponse>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'created_by');
    }

    /**
     * Scope to get shared responses.
     *
     * @param  Builder<SupportCannedResponse>  $query
     * @return Builder<SupportCannedResponse>
     */
    public function scopeShared(Builder $query): Builder
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope to filter by category.
     *
     * @param  Builder<SupportCannedResponse>  $query
     * @return Builder<SupportCannedResponse>
     */
    public function scopeByCategory(Builder $query, CannedResponseCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by creator.
     *
     * @param  Builder<SupportCannedResponse>  $query
     * @return Builder<SupportCannedResponse>
     */
    public function scopeByCreator(Builder $query, string $adminId): Builder
    {
        return $query->where('created_by', $adminId);
    }

    /**
     * Scope to search responses.
     *
     * @param  Builder<SupportCannedResponse>  $query
     * @return Builder<SupportCannedResponse>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where(function (Builder $q) use ($searchQuery) {
            $q->where('title', 'like', "%{$searchQuery}%")
                ->orWhere('content', 'like', "%{$searchQuery}%")
                ->orWhere('shortcut', 'like', "%{$searchQuery}%");
        });
    }

    /**
     * Scope to order by usage count (most popular first).
     *
     * @param  Builder<SupportCannedResponse>  $query
     * @return Builder<SupportCannedResponse>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('usage_count');
    }

    /**
     * Check if the response is shared.
     */
    public function isShared(): bool
    {
        return $this->is_shared;
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsageCount(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Render the content with variable substitution.
     *
     * @param  array<string, string>  $variables
     */
    public function renderContent(array $variables = []): string
    {
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }
}
