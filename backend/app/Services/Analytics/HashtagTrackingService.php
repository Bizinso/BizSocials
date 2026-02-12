<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Analytics\HashtagPerformance;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * HashtagTrackingService
 *
 * Manages hashtag performance tracking and suggestions.
 * Handles listing, tracking usage, retrieving top hashtags, and suggestions.
 */
final class HashtagTrackingService extends BaseService
{
    /**
     * List hashtag performance entries for a workspace with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = HashtagPerformance::forWorkspace($workspaceId);

        if (!empty($filters['platform'])) {
            $query->forPlatform($filters['platform']);
        }

        if (!empty($filters['search'])) {
            $query->where('hashtag', 'like', '%' . $filters['search'] . '%');
        }

        $sortBy = $filters['sort_by'] ?? 'avg_engagement';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Track or update hashtag usage metrics.
     *
     * @param  array<string, mixed>  $metrics
     */
    public function trackUsage(string $workspaceId, string $hashtag, string $platform, array $metrics): HashtagPerformance
    {
        return $this->transaction(function () use ($workspaceId, $hashtag, $platform, $metrics): HashtagPerformance {
            $existing = HashtagPerformance::forWorkspace($workspaceId)
                ->where('hashtag', $hashtag)
                ->where('platform', $platform)
                ->first();

            if ($existing !== null) {
                $newCount = $existing->usage_count + 1;
                $reach = $metrics['reach'] ?? 0;
                $engagement = $metrics['engagement'] ?? 0;
                $impressions = $metrics['impressions'] ?? 0;

                // Running average calculation
                $existing->update([
                    'usage_count' => $newCount,
                    'avg_reach' => round((($existing->avg_reach * $existing->usage_count) + $reach) / $newCount, 2),
                    'avg_engagement' => round((($existing->avg_engagement * $existing->usage_count) + $engagement) / $newCount, 2),
                    'avg_impressions' => round((($existing->avg_impressions * $existing->usage_count) + $impressions) / $newCount, 2),
                    'last_used_at' => Carbon::now(),
                ]);

                $this->log('Hashtag usage tracked (updated)', [
                    'workspace_id' => $workspaceId,
                    'hashtag' => $hashtag,
                    'platform' => $platform,
                ]);

                return $existing;
            }

            $performance = HashtagPerformance::create([
                'workspace_id' => $workspaceId,
                'hashtag' => $hashtag,
                'platform' => $platform,
                'usage_count' => 1,
                'avg_reach' => $metrics['reach'] ?? 0,
                'avg_engagement' => $metrics['engagement'] ?? 0,
                'avg_impressions' => $metrics['impressions'] ?? 0,
                'last_used_at' => Carbon::now(),
            ]);

            $this->log('Hashtag usage tracked (created)', [
                'workspace_id' => $workspaceId,
                'hashtag' => $hashtag,
                'platform' => $platform,
            ]);

            return $performance;
        });
    }

    /**
     * Get top performing hashtags for a workspace.
     */
    public function getTopHashtags(string $workspaceId, ?string $platform = null, int $limit = 20): Collection
    {
        $query = HashtagPerformance::forWorkspace($workspaceId);

        if ($platform !== null) {
            $query->forPlatform($platform);
        }

        return $query->topPerforming($limit)->get();
    }

    /**
     * Get hashtag suggestions based on performance data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSuggestions(string $workspaceId, string $platform): array
    {
        $topHashtags = $this->getTopHashtags($workspaceId, $platform, 10);

        return $topHashtags->map(fn (HashtagPerformance $hashtag) => [
            'hashtag' => $hashtag->hashtag,
            'avg_engagement' => $hashtag->avg_engagement,
            'avg_reach' => $hashtag->avg_reach,
            'usage_count' => $hashtag->usage_count,
            'reason' => $this->getSuggestionReason($hashtag),
        ])->values()->all();
    }

    /**
     * Generate a human-readable reason for why a hashtag is suggested.
     */
    private function getSuggestionReason(HashtagPerformance $hashtag): string
    {
        if ($hashtag->avg_engagement > 100) {
            return 'High engagement rate in your past posts';
        }

        if ($hashtag->avg_reach > 1000) {
            return 'Consistently strong reach performance';
        }

        if ($hashtag->usage_count >= 5) {
            return 'Frequently used with good results';
        }

        return 'Good performance in recent posts';
    }
}
