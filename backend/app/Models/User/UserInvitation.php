<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UserInvitation Model
 *
 * Represents an invitation to join a tenant organization.
 * Invitations have a token for verification and expire after a set period.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $email Invitee email address
 * @property TenantRole $role_in_tenant Role to assign when accepted
 * @property array|null $workspace_memberships Pre-configured workspace roles
 * @property string $invited_by Inviter user UUID
 * @property string $token Unique invitation token
 * @property InvitationStatus $status Invitation status
 * @property \Carbon\Carbon $expires_at Expiration timestamp
 * @property \Carbon\Carbon|null $accepted_at Acceptance timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read User $inviter
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> pending()
 * @method static Builder<static> expired()
 */
final class UserInvitation extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_invitations';

    /**
     * Token length for generated tokens.
     */
    public const TOKEN_LENGTH = 64;

    /**
     * Default number of days until invitation expires.
     */
    public const EXPIRES_IN_DAYS = 7;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'role_in_tenant',
        'workspace_memberships',
        'invited_by',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_in_tenant' => TenantRole::class,
            'workspace_memberships' => 'array',
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (UserInvitation $invitation): void {
            // Auto-generate token if not provided
            if (empty($invitation->token)) {
                $invitation->token = self::generateToken();
            }

            // Set default expiration if not provided
            if ($invitation->expires_at === null) {
                $invitation->expires_at = now()->addDays(self::EXPIRES_IN_DAYS);
            }
        });
    }

    /**
     * Get the tenant this invitation is for.
     *
     * @return BelongsTo<Tenant, UserInvitation>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who sent this invitation.
     *
     * @return BelongsTo<User, UserInvitation>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope to filter invitations by tenant.
     *
     * @param  Builder<UserInvitation>  $query
     * @return Builder<UserInvitation>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only pending invitations.
     *
     * @param  Builder<UserInvitation>  $query
     * @return Builder<UserInvitation>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::PENDING);
    }

    /**
     * Scope to get invitations past their expiration date.
     *
     * @param  Builder<UserInvitation>  $query
     * @return Builder<UserInvitation>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Find an invitation by its token.
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }

    /**
     * Generate a unique invitation token.
     */
    public static function generateToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Expire all old pending invitations.
     *
     * @return int Number of invitations marked as expired
     */
    public static function expireOldInvitations(): int
    {
        return self::pending()
            ->expired()
            ->update(['status' => InvitationStatus::EXPIRED]);
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING;
    }

    /**
     * Check if the invitation is expired (past expiration date or marked as expired).
     */
    public function isExpired(): bool
    {
        return $this->status === InvitationStatus::EXPIRED || $this->expires_at->isPast();
    }

    /**
     * Check if the invitation can be accepted (pending and not expired).
     */
    public function canBeAccepted(): bool
    {
        return $this->isPending() && !$this->expires_at->isPast();
    }

    /**
     * Accept the invitation.
     */
    public function accept(): void
    {
        $this->status = InvitationStatus::ACCEPTED;
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Revoke the invitation.
     */
    public function revoke(): void
    {
        $this->status = InvitationStatus::REVOKED;
        $this->save();
    }

    /**
     * Mark the invitation as expired.
     */
    public function markExpired(): void
    {
        $this->status = InvitationStatus::EXPIRED;
        $this->save();
    }

    /**
     * Get the workspace membership for a specific workspace.
     *
     * @return array{workspace_id: string, role: string}|null
     */
    public function getWorkspaceMembership(string $workspaceId): ?array
    {
        if ($this->workspace_memberships === null) {
            return null;
        }

        foreach ($this->workspace_memberships as $membership) {
            if (isset($membership['workspace_id']) && $membership['workspace_id'] === $workspaceId) {
                return $membership;
            }
        }

        return null;
    }
}
