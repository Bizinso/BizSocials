<?php

declare(strict_types=1);

namespace App\Models\Support;

use App\Enums\Support\SupportAttachmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * SupportTicketAttachment Model
 *
 * Represents a file attachment on a support ticket.
 *
 * @property string $id UUID primary key
 * @property string $ticket_id Ticket UUID
 * @property string|null $comment_id Comment UUID (if attached to a comment)
 * @property string $filename Stored filename
 * @property string $original_filename Original filename
 * @property string $file_path Storage path
 * @property string $mime_type MIME type
 * @property SupportAttachmentType $attachment_type Attachment type
 * @property int $file_size File size in bytes
 * @property string|null $uploaded_by User UUID who uploaded
 * @property bool $is_inline Whether displayed inline
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SupportTicket $ticket
 * @property-read SupportTicketComment|null $comment
 * @property-read User|null $uploader
 *
 * @method static Builder<static> forTicket(string $ticketId)
 * @method static Builder<static> forComment(string $commentId)
 * @method static Builder<static> images()
 * @method static Builder<static> documents()
 * @method static Builder<static> inline()
 */
final class SupportTicketAttachment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_ticket_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'comment_id',
        'filename',
        'original_filename',
        'file_path',
        'mime_type',
        'attachment_type',
        'file_size',
        'uploaded_by',
        'is_inline',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachment_type' => SupportAttachmentType::class,
            'file_size' => 'integer',
            'is_inline' => 'boolean',
        ];
    }

    /**
     * Get the ticket.
     *
     * @return BelongsTo<SupportTicket, SupportTicketAttachment>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the comment (if attached to one).
     *
     * @return BelongsTo<SupportTicketComment, SupportTicketAttachment>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(SupportTicketComment::class, 'comment_id');
    }

    /**
     * Get the user who uploaded the attachment.
     *
     * @return BelongsTo<User, SupportTicketAttachment>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope to filter by ticket.
     *
     * @param  Builder<SupportTicketAttachment>  $query
     * @return Builder<SupportTicketAttachment>
     */
    public function scopeForTicket(Builder $query, string $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope to filter by comment.
     *
     * @param  Builder<SupportTicketAttachment>  $query
     * @return Builder<SupportTicketAttachment>
     */
    public function scopeForComment(Builder $query, string $commentId): Builder
    {
        return $query->where('comment_id', $commentId);
    }

    /**
     * Scope to get images.
     *
     * @param  Builder<SupportTicketAttachment>  $query
     * @return Builder<SupportTicketAttachment>
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('attachment_type', SupportAttachmentType::IMAGE);
    }

    /**
     * Scope to get documents.
     *
     * @param  Builder<SupportTicketAttachment>  $query
     * @return Builder<SupportTicketAttachment>
     */
    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('attachment_type', SupportAttachmentType::DOCUMENT);
    }

    /**
     * Scope to get inline attachments.
     *
     * @param  Builder<SupportTicketAttachment>  $query
     * @return Builder<SupportTicketAttachment>
     */
    public function scopeInline(Builder $query): Builder
    {
        return $query->where('is_inline', true);
    }

    /**
     * Check if the attachment is an image.
     */
    public function isImage(): bool
    {
        return $this->attachment_type === SupportAttachmentType::IMAGE;
    }

    /**
     * Check if the attachment is a document.
     */
    public function isDocument(): bool
    {
        return $this->attachment_type === SupportAttachmentType::DOCUMENT;
    }

    /**
     * Check if the attachment is inline.
     */
    public function isInline(): bool
    {
        return $this->is_inline;
    }

    /**
     * Get the URL to the attachment.
     */
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the human-readable file size.
     */
    public function getHumanFileSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }
}
