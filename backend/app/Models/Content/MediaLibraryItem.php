<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MediaLibraryItem Model
 *
 * Represents a media file in the media library.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $uploaded_by_user_id User who uploaded the file
 * @property string|null $folder_id Folder UUID
 * @property string $file_name Stored file name
 * @property string $original_name Original file name
 * @property string $mime_type File MIME type
 * @property int $file_size File size in bytes
 * @property string $disk Storage disk name
 * @property string $path File path on disk
 * @property string $url Public URL to file
 * @property string|null $thumbnail_url Thumbnail URL
 * @property string|null $alt_text Alt text for accessibility
 * @property int|null $width Image/video width
 * @property int|null $height Image/video height
 * @property int|null $duration Video duration in seconds
 * @property array|null $tags Tags array
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Workspace $workspace
 * @property-read User $uploadedBy
 * @property-read MediaFolder|null $folder
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> inFolder(string $folderId)
 * @method static Builder<static> ofType(string $mimePrefix)
 * @method static Builder<static> search(string $term)
 */
final class MediaLibraryItem extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_library_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'uploaded_by_user_id',
        'folder_id',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'disk',
        'path',
        'url',
        'thumbnail_url',
        'alt_text',
        'width',
        'height',
        'duration',
        'tags',
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
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the workspace that this item belongs to.
     *
     * @return BelongsTo<Workspace, MediaLibraryItem>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who uploaded this item.
     *
     * @return BelongsTo<User, MediaLibraryItem>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Get the folder containing this item.
     *
     * @return BelongsTo<MediaFolder, MediaLibraryItem>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<MediaLibraryItem>  $query
     * @return Builder<MediaLibraryItem>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by folder.
     *
     * @param  Builder<MediaLibraryItem>  $query
     * @return Builder<MediaLibraryItem>
     */
    public function scopeInFolder(Builder $query, string $folderId): Builder
    {
        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope to filter by MIME type prefix.
     *
     * @param  Builder<MediaLibraryItem>  $query
     * @return Builder<MediaLibraryItem>
     */
    public function scopeOfType(Builder $query, string $mimePrefix): Builder
    {
        return $query->where('mime_type', 'like', $mimePrefix . '%');
    }

    /**
     * Scope to search in file names and alt text.
     *
     * @param  Builder<MediaLibraryItem>  $query
     * @return Builder<MediaLibraryItem>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('original_name', 'like', '%' . $term . '%')
                ->orWhere('alt_text', 'like', '%' . $term . '%');
        });
    }

    /**
     * Check if the item is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the item is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if the item is a document.
     */
    public function isDocument(): bool
    {
        return str_starts_with($this->mime_type, 'application/');
    }
}
