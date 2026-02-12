<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppConversationStatus;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $whatsapp_phone_number_id
 * @property string $customer_phone
 * @property string|null $customer_name
 * @property string|null $customer_profile_name
 * @property WhatsAppConversationStatus $status
 * @property string|null $assigned_to_user_id
 * @property string|null $assigned_to_team
 * @property string $priority
 * @property \Carbon\Carbon|null $last_message_at
 * @property \Carbon\Carbon|null $last_customer_message_at
 * @property \Carbon\Carbon|null $conversation_expires_at
 * @property bool $is_within_service_window
 * @property int $message_count
 * @property array|null $tags
 * @property int $internal_notes_count
 * @property \Carbon\Carbon|null $sla_breach_at
 * @property \Carbon\Carbon|null $first_response_at
 * @property array|null $metadata
 *
 * @property-read Workspace $workspace
 * @property-read WhatsAppPhoneNumber $phoneNumber
 * @property-read Collection<WhatsAppMessage> $messages
 * @property-read User|null $assignedUser
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> open()
 * @method static Builder<static> assignedTo(string $userId)
 * @method static Builder<static> unassigned()
 */
final class WhatsAppConversation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'workspace_id',
        'whatsapp_phone_number_id',
        'customer_phone',
        'customer_name',
        'customer_profile_name',
        'status',
        'assigned_to_user_id',
        'assigned_to_team',
        'priority',
        'last_message_at',
        'last_customer_message_at',
        'conversation_expires_at',
        'is_within_service_window',
        'message_count',
        'tags',
        'internal_notes_count',
        'sla_breach_at',
        'first_response_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppConversationStatus::class,
            'last_message_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'conversation_expires_at' => 'datetime',
            'is_within_service_window' => 'boolean',
            'message_count' => 'integer',
            'tags' => 'array',
            'internal_notes_count' => 'integer',
            'sla_breach_at' => 'datetime',
            'first_response_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, WhatsAppConversation> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<WhatsAppPhoneNumber, WhatsAppConversation> */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    /** @return HasMany<WhatsAppMessage> */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')->orderByDesc('created_at');
    }

    /** @return BelongsTo<User, WhatsAppConversation> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /** @param Builder<WhatsAppConversation> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /** @param Builder<WhatsAppConversation> $query */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WhatsAppConversationStatus::ACTIVE,
            WhatsAppConversationStatus::PENDING,
        ]);
    }

    /** @param Builder<WhatsAppConversation> $query */
    public function scopeAssignedTo(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /** @param Builder<WhatsAppConversation> $query */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to_user_id');
    }

    public function isWithinServiceWindow(): bool
    {
        return $this->last_customer_message_at !== null
            && $this->last_customer_message_at->gt(now()->subHours(24));
    }

    public function canSendFreeForm(): bool
    {
        return $this->isWithinServiceWindow();
    }

    public function markResolved(): void
    {
        $this->update(['status' => WhatsAppConversationStatus::RESOLVED]);
    }

    public function assignTo(?User $user, ?string $team = null): void
    {
        $this->update([
            'assigned_to_user_id' => $user?->id,
            'assigned_to_team' => $team,
        ]);
    }
}
