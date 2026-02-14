<?php

declare(strict_types=1);

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFinding extends Model
{
    use HasUuids;

    protected $fillable = [
        'audit_report_id',
        'type',
        'severity',
        'location',
        'description',
        'evidence',
        'recommendation',
        'status',
        'fixed_at',
    ];

    protected $casts = [
        'fixed_at' => 'datetime',
    ];

    /**
     * Get the audit report that owns this finding.
     */
    public function auditReport(): BelongsTo
    {
        return $this->belongsTo(AuditReport::class);
    }
}
