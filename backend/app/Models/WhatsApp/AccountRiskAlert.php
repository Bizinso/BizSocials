<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\AlertSeverity;
use App\Enums\WhatsApp\AlertType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountRiskAlert extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'whatsapp_business_account_id',
        'alert_type',
        'severity',
        'title',
        'description',
        'recommended_action',
        'auto_action_taken',
        'acknowledged_at',
        'acknowledged_by_user_id',
        'resolved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'alert_type' => AlertType::class,
            'severity' => AlertSeverity::class,
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WhatsAppBusinessAccount, self> */
    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBusinessAccount::class, 'whatsapp_business_account_id');
    }

    /** @return BelongsTo<User, self> */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function acknowledge(User $user): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $user->id,
        ]);
    }

    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeForSeverity($query, AlertSeverity $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeForAccount($query, string $accountId)
    {
        return $query->where('whatsapp_business_account_id', $accountId);
    }
}
