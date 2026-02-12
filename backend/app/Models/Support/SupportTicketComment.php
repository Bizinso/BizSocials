<?php

declare(strict_types=1);

namespace App\Models\Support;

use App\Enums\Support\SupportCommentType;
use App\Models\Platform\SuperAdminUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SupportTicketComment Model
 *
 * Represents a comment or reply on a support ticket.
 *
 * @property string $id UUID primary key
 * @property string $ticket_id Ticket UUID
 * @property string|null $user_id User UUID (if customer comment)
 * @property string|null $admin_id Admin UUID (if staff comment)
 * @property string|null $author_name Author name
 * @property string|null $author_email Author email
 * @property string $content Comment content
 * @property SupportCommentType $comment_type Type of comment
 * @property bool $is_internal Whether the comment is internal
 * @property array|null $metadata Additional metadata JSON
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SupportTicket $ticket
 * @property-read User|null $user
 * @property-read SuperAdminUser|null $admin
 * @property-read Collection<int, SupportTicketAttachment> $attachments
 *
 * @method static Builder<static> forTicket(string $ticketId)
 * @method static Builder<static> public()
 * @method static Builder<static> internal()
 * @method static Builder<static> replies()
 * @method static Builder<static> notes()
 * @method static Builder<static> system()
 */
final class SupportTicketComment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_ticket_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'admin_id',
        'author_name',
        'author_email',
        'content',
        'comment_type',
        'is_internal',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'comment_type' => SupportCommentType::class,
            'is_internal' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the ticket.
     *
     * @return BelongsTo<SupportTicket, SupportTicketComment>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the user (customer).
     *
     * @return BelongsTo<User, SupportTicketComment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin (support staff).
     *
     * @return BelongsTo<SuperAdminUser, SupportTicketComment>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'admin_id');
    }

    /**
     * Get the attachments for this comment.
     *
     * @return HasMany<SupportTicketAttachment>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'comment_id');
    }

    /**
     * Scope to filter by ticket.
     *
     * @param  Builder<SupportTicketComment>  $query
     * @return Builder<SupportTicketComment>
     */
    public function scopeForTicket(Builder $query, string $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope to get public comments (visible to customer).
     *
     * @param  Builder<SupportTicketComment>  $query
     * @return Builder<SupportTicketComment>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_internal', false)
            ->where('comment_type', SupportCommentType::REPLY);
    }

    /**
     * Scope to get internal comments.
     *
     * @param  Builder<SupportTicketComment>  $query
     * @return Builder<SupportTicketComment>
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope to get replies.
     *
     * @param  Builder<SupportTicketComment>  $query
     * @return Builder<SupportTicketComment>
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->where('comment_type', SupportCommentType::REPLY);
    }

    /**
     * Scope to get notes.
     *
     * @param  Builder<SupportTicketComment>  $query
     * @return Builder<SupportTicketComment>
     */
    public function scopeNotes(Builder $query): Builder
    {
        return $query->where('comment_type', SupportCommentType::NOTE);
    }

    /**
     * Scope to get system comments.
     *
     * @param  Builder<SupportTicketComment>  $query
     * @return Builder<SupportTicketComment>
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereIn('comment_type', [
            SupportCommentType::STATUS_CHANGE,
            SupportCommentType::ASSIGNMENT,
            SupportCommentType::SYSTEM,
        ]);
    }

    /**
     * Check if the comment is public.
     */
    public function isPublic(): bool
    {
        return !$this->is_internal && $this->comment_type === SupportCommentType::REPLY;
    }

    /**
     * Check if the comment is internal.
     */
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    /**
     * Check if the comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->comment_type === SupportCommentType::REPLY;
    }

    /**
     * Check if the comment is a note.
     */
    public function isNote(): bool
    {
        return $this->comment_type === SupportCommentType::NOTE;
    }

    /**
     * Check if the comment is a system comment.
     */
    public function isSystem(): bool
    {
        return in_array($this->comment_type, [
            SupportCommentType::STATUS_CHANGE,
            SupportCommentType::ASSIGNMENT,
            SupportCommentType::SYSTEM,
        ], true);
    }

    /**
     * Get the author name.
     */
    public function getAuthorName(): string
    {
        if ($this->author_name) {
            return $this->author_name;
        }

        if ($this->admin) {
            return $this->admin->name;
        }

        if ($this->user) {
            return $this->user->name;
        }

        return 'System';
    }

    /**
     * Get the author email.
     */
    public function getAuthorEmail(): ?string
    {
        if ($this->author_email) {
            return $this->author_email;
        }

        if ($this->admin) {
            return $this->admin->email;
        }

        if ($this->user) {
            return $this->user->email;
        }

        return null;
    }
}
