<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppOptInSource;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $phone_number
 * @property string|null $customer_name
 * @property WhatsAppOptInSource $source
 * @property \Carbon\Carbon $opted_in_at
 * @property \Carbon\Carbon|null $opted_out_at
 * @property string|null $opt_in_proof
 * @property bool $is_active
 * @property array|null $tags
 * @property array|null $metadata
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forPhone(string $phone)
 * @method static Builder<static> forWorkspace(string $workspaceId)
 */
final class WhatsAppOptIn extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_opt_ins';

    protected $fillable = [
        'workspace_id',
        'phone_number',
        'customer_name',
        'source',
        'opted_in_at',
        'opted_out_at',
        'opt_in_proof',
        'is_active',
        'tags',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source' => WhatsAppOptInSource::class,
            'opted_in_at' => 'datetime',
            'opted_out_at' => 'datetime',
            'is_active' => 'boolean',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, WhatsAppOptIn> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @param Builder<WhatsAppOptIn> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<WhatsAppOptIn> $query */
    public function scopeForPhone(Builder $query, string $phone): Builder
    {
        return $query->where('phone_number', $phone);
    }

    /** @param Builder<WhatsAppOptIn> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function optOut(): void
    {
        $this->update([
            'opted_out_at' => now(),
            'is_active' => false,
        ]);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}
