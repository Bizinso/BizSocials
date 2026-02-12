<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PlatformPolicy extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'platform',
        'policy_version',
        'policy_name',
        'description',
        'effective_date',
        'source_url',
        'enforcement_actions',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'enforcement_actions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<PolicyChangeLog> */
    public function changeLogs(): HasMany
    {
        return $this->hasMany(PolicyChangeLog::class, 'platform_policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
