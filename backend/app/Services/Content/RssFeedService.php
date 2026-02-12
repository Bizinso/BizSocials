<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\RssFeed;
use App\Models\Content\RssFeedItem;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RssFeedService extends BaseService
{
    /**
     * Add a new RSS feed.
     */
    public function addFeed(
        string $workspaceId,
        string $url,
        string $name,
        ?string $categoryId = null
    ): RssFeed {
        $feed = RssFeed::create([
            'workspace_id' => $workspaceId,
            'url' => $url,
            'name' => $name,
            'is_active' => true,
            'auto_schedule' => false,
            'category_id' => $categoryId,
            'fetch_interval_hours' => 24,
        ]);

        $this->log('RSS feed added', ['feed_id' => $feed->id]);

        return $feed;
    }

    /**
     * Fetch items from an RSS feed.
     */
    public function fetchItems(RssFeed $feed): int
    {
        try {
            $sp = new \SimplePie\SimplePie();
            $sp->set_feed_url($feed->url);
            $sp->init();

            $count = 0;

            foreach ($sp->get_items() as $item) {
                $guid = $item->get_id();

                // Check if item already exists
                if (RssFeedItem::where('rss_feed_id', $feed->id)
                    ->where('guid', $guid)
                    ->exists()) {
                    continue;
                }

                // Get image URL
                $imageUrl = null;
                $enclosure = $item->get_enclosure();
                if ($enclosure !== null && str_starts_with($enclosure->get_type(), 'image/')) {
                    $imageUrl = $enclosure->get_link();
                }

                RssFeedItem::create([
                    'rss_feed_id' => $feed->id,
                    'guid' => $guid,
                    'title' => $item->get_title(),
                    'link' => $item->get_permalink(),
                    'description' => $item->get_description(),
                    'image_url' => $imageUrl,
                    'published_at' => $item->get_date('Y-m-d H:i:s'),
                    'is_used' => false,
                ]);

                $count++;
            }

            // Update last fetched timestamp
            $feed->update(['last_fetched_at' => now()]);

            $this->log('RSS feed fetched', [
                'feed_id' => $feed->id,
                'new_items' => $count,
            ]);

            return $count;
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to fetch RSS feed');
        }
    }

    /**
     * List RSS feeds for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function listFeeds(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = RssFeed::forWorkspace($workspaceId)
            ->with('category');

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * List items from a feed.
     *
     * @param array<string, mixed> $filters
     */
    public function listItems(string $feedId, array $filters = []): LengthAwarePaginator
    {
        $query = RssFeedItem::where('rss_feed_id', $feedId);

        // Filter by used status
        if (isset($filters['is_used'])) {
            $query->where('is_used', (bool) $filters['is_used']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark an item as used.
     */
    public function markUsed(RssFeedItem $item): bool
    {
        $updated = $item->update(['is_used' => true]);

        $this->log('RSS feed item marked as used', ['item_id' => $item->id]);

        return $updated;
    }

    /**
     * Delete a feed and its items.
     */
    public function delete(RssFeed $feed): bool
    {
        return $this->transaction(function () use ($feed) {
            // Delete all items
            $feed->items()->delete();

            // Delete feed
            $deleted = $feed->delete();

            $this->log('RSS feed deleted', ['feed_id' => $feed->id]);

            return $deleted;
        });
    }
}
