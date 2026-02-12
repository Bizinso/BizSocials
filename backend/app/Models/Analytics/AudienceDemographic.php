<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use App\Models\Social\SocialAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AudienceDemographic Model
 *
 * Stores daily audience demographic snapshots for a social account.
 *
 * @property string $id UUID primary key
 * @property string $social_account_id Social account UUID
 * @property \Carbon\Carbon $snapshot_date Date of the snapshot
 * @property array|null $age_ranges Age range distribution
 * @property array|null $gender_split Gender distribution
 * @property array|null $top_countries Top countries by audience
 * @property array|null $top_cities Top cities by audience
 * @property int $follower_count Follower count at snapshot time
 * @property \Carbon\Carbon|null $created_at
 *
 * @property-read SocialAccount $socialAccount
 *
 * @method static Builder<static> forAccount(string $socialAccountId)
 * @method static Builder<static> forDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 */
final class AudienceDemographic extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audience_demographics';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'social_account_id',
        'snapshot_date',
        'age_ranges',
        'gender_split',
        'top_countries',
        'top_cities',
        'follower_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'age_ranges' => 'array',
            'gender_split' => 'array',
            'top_countries' => 'array',
            'top_cities' => 'array',
        ];
    }

    /**
     * Get the social account that this demographic belongs to.
     *
     * @return BelongsTo<SocialAccount, AudienceDemographic>
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Scope to filter by social account.
     *
     * @param  Builder<AudienceDemographic>  $query
     * @return Builder<AudienceDemographic>
     */
    public function scopeForAccount(Builder $query, string $socialAccountId): Builder
    {
        return $query->where('social_account_id', $socialAccountId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<AudienceDemographic>  $query
     * @return Builder<AudienceDemographic>
     */
    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('snapshot_date', [$start->toDateString(), $end->toDateString()]);
    }
}
