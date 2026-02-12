<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Enums\Listening\SentimentType;
use App\Models\Listening\KeywordMention;
use App\Models\Listening\MonitoredKeyword;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class KeywordMonitoringService extends BaseService
{
    public function __construct(
        private readonly BasicSentimentService $sentimentService,
    ) {}

    /**
     * List monitored keywords for a workspace.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listKeywords(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = MonitoredKeyword::forWorkspace($workspaceId);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new monitored keyword.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(string $workspaceId, array $data): MonitoredKeyword
    {
        $keyword = MonitoredKeyword::create([
            'workspace_id' => $workspaceId,
            'keyword' => $data['keyword'],
            'platforms' => $data['platforms'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notify_on_match' => $data['notify_on_match'] ?? false,
            'match_count' => 0,
        ]);

        $this->log('Monitored keyword created', ['keyword_id' => $keyword->id]);

        return $keyword;
    }

    /**
     * Update a monitored keyword.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MonitoredKeyword $keyword, array $data): MonitoredKeyword
    {
        $keyword->update($data);

        $this->log('Monitored keyword updated', ['keyword_id' => $keyword->id]);

        return $keyword->fresh();
    }

    /**
     * Delete a monitored keyword.
     */
    public function delete(MonitoredKeyword $keyword): void
    {
        $this->transaction(function () use ($keyword): void {
            $keyword->mentions()->delete();
            $keyword->delete();

            $this->log('Monitored keyword deleted', ['keyword_id' => $keyword->id]);
        });
    }

    /**
     * Get mentions for a keyword.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getMentions(string $keywordId, array $filters = []): LengthAwarePaginator
    {
        $query = KeywordMention::where('keyword_id', $keywordId);

        if (isset($filters['sentiment'])) {
            $query->where('sentiment', $filters['sentiment']);
        }

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Record a mention for a keyword.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordMention(MonitoredKeyword $keyword, array $data): KeywordMention
    {
        $sentiment = SentimentType::UNKNOWN;
        if (! empty($data['content_text'])) {
            $sentiment = $this->sentimentService->analyze($data['content_text']);
        }

        $mention = KeywordMention::create([
            'keyword_id' => $keyword->id,
            'platform' => $data['platform'],
            'platform_item_id' => $data['platform_item_id'] ?? null,
            'author_name' => $data['author_name'] ?? null,
            'content_text' => $data['content_text'] ?? null,
            'sentiment' => $sentiment->value,
            'url' => $data['url'] ?? null,
            'platform_created_at' => $data['platform_created_at'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'created_at' => now(),
        ]);

        $keyword->increment('match_count');

        $this->log('Keyword mention recorded', [
            'keyword_id' => $keyword->id,
            'mention_id' => $mention->id,
            'sentiment' => $sentiment->value,
        ]);

        return $mention;
    }

    /**
     * Get sentiment breakdown for a keyword.
     *
     * @return array<string, int>
     */
    public function getSentimentBreakdown(string $keywordId): array
    {
        $mentions = KeywordMention::where('keyword_id', $keywordId)
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        return [
            'positive' => $mentions[SentimentType::POSITIVE->value] ?? 0,
            'negative' => $mentions[SentimentType::NEGATIVE->value] ?? 0,
            'neutral' => $mentions[SentimentType::NEUTRAL->value] ?? 0,
            'unknown' => $mentions[SentimentType::UNKNOWN->value] ?? 0,
        ];
    }
}
