<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Enums\Feedback\ReleaseType;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ChangelogSubscription Model
 *
 * Represents a subscription to changelog/release note updates.
 *
 * @property string $id UUID primary key
 * @property string $email Subscriber email
 * @property string|null $user_id User UUID
 * @property string|null $tenant_id Tenant UUID
 * @property bool $notify_major Notify for major releases
 * @property bool $notify_minor Notify for minor releases
 * @property bool $notify_patch Notify for patch releases
 * @property bool $is_active Active subscription
 * @property \Carbon\Carbon|null $unsubscribed_at Unsubscribe date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User|null $user
 * @property-read Tenant|null $tenant
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forEmail(string $email)
 * @method static Builder<static> notifyFor(ReleaseType $type)
 */
final class ChangelogSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'changelog_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'user_id',
        'tenant_id',
        'notify_major',
        'notify_minor',
        'notify_patch',
        'is_active',
        'unsubscribed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify_major' => 'boolean',
            'notify_minor' => 'boolean',
            'notify_patch' => 'boolean',
            'is_active' => 'boolean',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, ChangelogSubscription>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, ChangelogSubscription>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope to get active subscriptions.
     *
     * @param  Builder<ChangelogSubscription>  $query
     * @return Builder<ChangelogSubscription>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by email.
     *
     * @param  Builder<ChangelogSubscription>  $query
     * @return Builder<ChangelogSubscription>
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to get subscriptions that should be notified for a release type.
     *
     * @param  Builder<ChangelogSubscription>  $query
     * @return Builder<ChangelogSubscription>
     */
    public function scopeNotifyFor(Builder $query, ReleaseType $type): Builder
    {
        return match ($type) {
            ReleaseType::MAJOR => $query->where('notify_major', true),
            ReleaseType::MINOR => $query->where('notify_minor', true),
            ReleaseType::PATCH, ReleaseType::HOTFIX => $query->where('notify_patch', true),
            default => $query->where('notify_minor', true),
        };
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Unsubscribe.
     */
    public function unsubscribe(): void
    {
        $this->is_active = false;
        $this->unsubscribed_at = now();
        $this->save();
    }

    /**
     * Resubscribe.
     */
    public function resubscribe(): void
    {
        $this->is_active = true;
        $this->unsubscribed_at = null;
        $this->save();
    }

    /**
     * Check if subscription should be notified for a release type.
     */
    public function shouldNotifyFor(ReleaseType $type): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return match ($type) {
            ReleaseType::MAJOR => $this->notify_major,
            ReleaseType::MINOR => $this->notify_minor,
            ReleaseType::PATCH, ReleaseType::HOTFIX => $this->notify_patch,
            default => $this->notify_minor,
        };
    }
}
