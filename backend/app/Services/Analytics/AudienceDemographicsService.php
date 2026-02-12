<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Analytics\AudienceDemographic;
use App\Models\Social\SocialAccount;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * AudienceDemographicsService
 *
 * Manages audience demographic snapshots for social accounts.
 * Handles retrieval, history, and workspace-level overview aggregation.
 */
final class AudienceDemographicsService extends BaseService
{
    /**
     * Get the latest demographic snapshot for a social account.
     */
    public function getLatest(string $socialAccountId): ?AudienceDemographic
    {
        return AudienceDemographic::forAccount($socialAccountId)
            ->orderByDesc('snapshot_date')
            ->first();
    }

    /**
     * Get demographic history for a social account over a number of days.
     */
    public function getHistory(string $socialAccountId, int $days = 30): Collection
    {
        $start = Carbon::today()->subDays($days);
        $end = Carbon::today();

        return AudienceDemographic::forAccount($socialAccountId)
            ->forDateRange($start, $end)
            ->orderByDesc('snapshot_date')
            ->get();
    }

    /**
     * Create a demographic snapshot for a social account.
     *
     * @param  array<string, mixed>  $data
     */
    public function snapshot(string $socialAccountId, array $data): AudienceDemographic
    {
        return $this->transaction(function () use ($socialAccountId, $data): AudienceDemographic {
            $demographic = AudienceDemographic::updateOrCreate(
                [
                    'social_account_id' => $socialAccountId,
                    'snapshot_date' => $data['snapshot_date'] ?? Carbon::today()->toDateString(),
                ],
                [
                    'age_ranges' => $data['age_ranges'] ?? null,
                    'gender_split' => $data['gender_split'] ?? null,
                    'top_countries' => $data['top_countries'] ?? null,
                    'top_cities' => $data['top_cities'] ?? null,
                    'follower_count' => $data['follower_count'] ?? 0,
                ]
            );

            $this->log('Audience demographic snapshot created', [
                'social_account_id' => $socialAccountId,
                'snapshot_date' => $demographic->snapshot_date->toDateString(),
            ]);

            return $demographic;
        });
    }

    /**
     * Get aggregated audience overview for all social accounts in a workspace.
     *
     * @return array<string, mixed>
     */
    public function getWorkspaceOverview(string $workspaceId): array
    {
        $socialAccounts = SocialAccount::where('workspace_id', $workspaceId)->get();

        $totalFollowers = 0;
        $aggregatedAgeRanges = [];
        $aggregatedGenderSplit = [];
        $aggregatedCountries = [];
        $aggregatedCities = [];
        $accountSnapshots = [];

        foreach ($socialAccounts as $account) {
            $latest = $this->getLatest($account->id);

            if ($latest === null) {
                continue;
            }

            $totalFollowers += $latest->follower_count;

            // Aggregate age ranges
            if (is_array($latest->age_ranges)) {
                foreach ($latest->age_ranges as $range => $count) {
                    $aggregatedAgeRanges[$range] = ($aggregatedAgeRanges[$range] ?? 0) + $count;
                }
            }

            // Aggregate gender split
            if (is_array($latest->gender_split)) {
                foreach ($latest->gender_split as $gender => $count) {
                    $aggregatedGenderSplit[$gender] = ($aggregatedGenderSplit[$gender] ?? 0) + $count;
                }
            }

            // Aggregate countries
            if (is_array($latest->top_countries)) {
                foreach ($latest->top_countries as $entry) {
                    $country = $entry['country'] ?? '';
                    $count = $entry['count'] ?? 0;
                    $aggregatedCountries[$country] = ($aggregatedCountries[$country] ?? 0) + $count;
                }
            }

            // Aggregate cities
            if (is_array($latest->top_cities)) {
                foreach ($latest->top_cities as $entry) {
                    $city = $entry['city'] ?? '';
                    $count = $entry['count'] ?? 0;
                    $aggregatedCities[$city] = ($aggregatedCities[$city] ?? 0) + $count;
                }
            }

            $accountSnapshots[] = [
                'social_account_id' => $account->id,
                'platform' => $account->platform,
                'follower_count' => $latest->follower_count,
                'snapshot_date' => $latest->snapshot_date->toDateString(),
            ];
        }

        // Sort and limit countries/cities
        arsort($aggregatedCountries);
        arsort($aggregatedCities);

        $topCountries = array_map(
            fn (string $country, int $count) => ['country' => $country, 'count' => $count],
            array_keys(array_slice($aggregatedCountries, 0, 10, true)),
            array_values(array_slice($aggregatedCountries, 0, 10, true))
        );

        $topCities = array_map(
            fn (string $city, int $count) => ['city' => $city, 'count' => $count],
            array_keys(array_slice($aggregatedCities, 0, 10, true)),
            array_values(array_slice($aggregatedCities, 0, 10, true))
        );

        return [
            'total_followers' => $totalFollowers,
            'age_ranges' => $aggregatedAgeRanges,
            'gender_split' => $aggregatedGenderSplit,
            'top_countries' => $topCountries,
            'top_cities' => $topCities,
            'accounts' => $accountSnapshots,
        ];
    }
}
