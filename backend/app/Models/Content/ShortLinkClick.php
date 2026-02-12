<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ShortLinkClick Model
 *
 * Represents a click on a short link.
 *
 * @property string $id UUID primary key
 * @property string $short_link_id ShortLink UUID
 * @property string|null $ip_address IP address of the visitor
 * @property string|null $user_agent User agent string
 * @property string|null $referer HTTP referer
 * @property string|null $country Country code
 * @property string|null $device_type Device type (desktop/mobile/tablet)
 * @property \Carbon\Carbon $clicked_at Click timestamp
 *
 * @property-read ShortLink $shortLink
 */
final class ShortLinkClick extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'short_link_clicks';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'short_link_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'device_type',
        'clicked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * Get the short link that this click belongs to.
     *
     * @return BelongsTo<ShortLink, ShortLinkClick>
     */
    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }
}
