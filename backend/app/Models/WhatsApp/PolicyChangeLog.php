<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PolicyChangeLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'platform_policy_id',
        'change_type',
        'old_value',
        'new_value',
        'description',
        'notified_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PlatformPolicy, self> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(PlatformPolicy::class, 'platform_policy_id');
    }
}
