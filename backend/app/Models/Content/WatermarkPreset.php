<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WatermarkPreset Model
 *
 * Represents a reusable watermark preset for a workspace.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Preset name
 * @property string $type Preset type (image or text)
 * @property string|null $image_path Path to watermark image
 * @property string|null $text Watermark text
 * @property string $position Watermark position
 * @property int $opacity Watermark opacity (0-100)
 * @property int $scale Watermark scale percentage
 * @property bool $is_default Whether this is the default preset
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> default()
 */
final class WatermarkPreset extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'watermark_presets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'type',
        'image_path',
        'text',
        'position',
        'opacity',
        'scale',
        'is_default',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opacity' => 'integer',
            'scale' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the workspace that this preset belongs to.
     *
     * @return BelongsTo<Workspace, WatermarkPreset>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<WatermarkPreset>  $query
     * @return Builder<WatermarkPreset>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter default presets.
     *
     * @param  Builder<WatermarkPreset>  $query
     * @return Builder<WatermarkPreset>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
