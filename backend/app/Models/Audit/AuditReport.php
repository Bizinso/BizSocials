<?php

declare(strict_types=1);

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'feature_area',
        'findings',
        'summary',
        'recommendations',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'findings' => 'array',
        'summary' => 'array',
        'recommendations' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the findings for this audit report.
     */
    public function auditFindings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }
}
