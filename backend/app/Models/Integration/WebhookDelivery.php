<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookDelivery Model
 *
 * Represents a delivery attempt for an outgoing webhook.
 *
 * @property string $id UUID primary key
 * @property string $webhook_endpoint_id WebhookEndpoint UUID
 * @property string $event Event name
 * @property array $payload Event payload
 * @property int|null $response_code HTTP response code
 * @property string|null $response_body HTTP response body
 * @property int|null $duration_ms Request duration in milliseconds
 * @property \Carbon\Carbon|null $delivered_at Delivery timestamp
 * @property \Carbon\Carbon|null $created_at
 *
 * @property-read WebhookEndpoint $endpoint
 */
final class WebhookDelivery extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhook_deliveries';

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
        'webhook_endpoint_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'duration_ms',
        'delivered_at',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the endpoint that this delivery belongs to.
     *
     * @return BelongsTo<WebhookEndpoint, WebhookDelivery>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
