<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\SentimentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KeywordMention Model
 *
 * Represents a mention found for a monitored keyword.
 *
 * @property string $id UUID primary key
 * @property string $keyword_id MonitoredKeyword UUID
 * @property string $platform Platform where mention was found
 * @property string|null $platform_item_id Platform-specific item ID
 * @property string|null $author_name Author of the mention
 * @property string|null $content_text Content of the mention
 * @property SentimentType $sentiment Sentiment classification
 * @property string|null $url URL of the mention
 * @property \Carbon\Carbon|null $platform_created_at When the mention was created on the platform
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon|null $created_at
 *
 * @property-read MonitoredKeyword $keyword
 */
final class KeywordMention extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_mentions';

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
        'keyword_id',
        'platform',
        'platform_item_id',
        'author_name',
        'content_text',
        'sentiment',
        'url',
        'platform_created_at',
        'metadata',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sentiment' => SentimentType::class,
            'platform_created_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the keyword that this mention belongs to.
     *
     * @return BelongsTo<MonitoredKeyword, KeywordMention>
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(MonitoredKeyword::class, 'keyword_id');
    }
}
