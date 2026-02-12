<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $platform
 * @property string $platform_account_id
 * @property string $verify_token
 * @property array $subscribed_fields
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_received_at
 * @property int $failure_count
 *
 * @property-read Tenant $tenant
 *
 * @method static Builder<static> forPlatform(string $platform)
 * @method static Builder<static> active()
 */
final class WebhookSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'webhook_subscriptions';

    protected $fillable = [
        'tenant_id',
        'platform',
        'platform_account_id',
        'verify_token',
        'subscribed_fields',
        'is_active',
        'last_received_at',
        'failure_count',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_fields' => 'array',
            'is_active' => 'boolean',
            'last_received_at' => 'datetime',
            'failure_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Tenant, WebhookSubscription> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @param Builder<WebhookSubscription> $query */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /** @param Builder<WebhookSubscription> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function recordReceived(): void
    {
        $this->update([
            'last_received_at' => now(),
            'failure_count' => 0,
        ]);
    }

    public function recordFailure(): void
    {
        $this->increment('failure_count');
    }
}
