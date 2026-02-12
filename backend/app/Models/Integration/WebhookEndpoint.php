<?php

declare(strict_types=1);

namespace App\Models\Integration;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WebhookEndpoint Model
 *
 * Represents an outgoing webhook endpoint configured for a workspace.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $url Endpoint URL
 * @property string $secret HMAC signing secret
 * @property array $events Subscribed events
 * @property bool $is_active Whether the endpoint is active
 * @property int $failure_count Consecutive failure count
 * @property \Carbon\Carbon|null $last_triggered_at Last trigger timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WebhookDelivery> $deliveries
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 * @method static Builder<static> forEvent(string $event)
 */
final class WebhookEndpoint extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhook_endpoints';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'url',
        'secret',
        'events',
        'is_active',
        'failure_count',
        'last_triggered_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace that this endpoint belongs to.
     *
     * @return BelongsTo<Workspace, WebhookEndpoint>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the deliveries for this endpoint.
     *
     * @return HasMany<WebhookDelivery>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<WebhookEndpoint>  $query
     * @return Builder<WebhookEndpoint>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter active endpoints.
     *
     * @param  Builder<WebhookEndpoint>  $query
     * @return Builder<WebhookEndpoint>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter endpoints subscribed to a given event.
     *
     * @param  Builder<WebhookEndpoint>  $query
     * @return Builder<WebhookEndpoint>
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }
}
