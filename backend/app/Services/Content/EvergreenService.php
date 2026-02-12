<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\Content\PostStatus;
use App\Models\Content\EvergreenPostPool;
use App\Models\Content\EvergreenRule;
use App\Models\Content\Post;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

final class EvergreenService extends BaseService
{
    /**
     * Create a new evergreen rule.
     *
     * @param array<string, mixed> $data
     */
    public function createRule(string $workspaceId, array $data): EvergreenRule
    {
        $rule = EvergreenRule::create([
            'workspace_id' => $workspaceId,
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
            'source_category_id' => $data['source_category_id'] ?? null,
            'social_account_ids' => $data['social_account_ids'],
            'repost_interval_days' => $data['repost_interval_days'],
            'max_reposts' => $data['max_reposts'],
            'time_slots' => $data['time_slots'] ?? null,
            'content_variation' => $data['content_variation'] ?? false,
        ]);

        $this->log('Evergreen rule created', ['rule_id' => $rule->id]);

        return $rule;
    }

    /**
     * Update an evergreen rule.
     *
     * @param array<string, mixed> $data
     */
    public function updateRule(EvergreenRule $rule, array $data): EvergreenRule
    {
        $rule->update($data);

        $this->log('Evergreen rule updated', ['rule_id' => $rule->id]);

        return $rule;
    }

    /**
     * Build the post pool for a rule.
     */
    public function buildPool(EvergreenRule $rule): int
    {
        return $this->transaction(function () use ($rule) {
            // Clear existing pool
            $rule->poolEntries()->delete();

            // Find published posts in source category
            $query = Post::forWorkspace($rule->workspace_id)
                ->withStatus(PostStatus::PUBLISHED);

            if ($rule->source_category_id !== null) {
                $query->where('category_id', $rule->source_category_id);
            }

            $posts = $query->get();

            $count = 0;

            foreach ($posts as $post) {
                $nextRepostAt = Carbon::now()->addDays($rule->repost_interval_days);

                EvergreenPostPool::create([
                    'evergreen_rule_id' => $rule->id,
                    'post_id' => $post->id,
                    'repost_count' => 0,
                    'next_repost_at' => $nextRepostAt,
                    'is_active' => true,
                ]);

                $count++;
            }

            $this->log('Evergreen pool built', [
                'rule_id' => $rule->id,
                'posts_added' => $count,
            ]);

            return $count;
        });
    }

    /**
     * Process reposts for all active rules.
     */
    public function processReposts(): int
    {
        $count = 0;

        $rules = EvergreenRule::active()->get();

        foreach ($rules as $rule) {
            $dueEntries = $rule->poolEntries()
                ->active()
                ->dueForRepost()
                ->with('post')
                ->get();

            foreach ($dueEntries as $entry) {
                // Check if max reposts reached
                if ($entry->repost_count >= $rule->max_reposts) {
                    $entry->update(['is_active' => false]);
                    continue;
                }

                // Create new draft post (clone)
                $originalPost = $entry->post;

                $newPost = Post::create([
                    'workspace_id' => $rule->workspace_id,
                    'created_by_user_id' => $originalPost->created_by_user_id,
                    'content_text' => $originalPost->content_text,
                    'content_variations' => $originalPost->content_variations,
                    'post_type' => $originalPost->post_type,
                    'status' => PostStatus::DRAFT,
                    'hashtags' => $originalPost->hashtags,
                    'mentions' => $originalPost->mentions,
                    'link_url' => $originalPost->link_url,
                    'first_comment' => $originalPost->first_comment,
                    'metadata' => array_merge($originalPost->metadata ?? [], [
                        'evergreen_repost' => true,
                        'evergreen_rule_id' => $rule->id,
                        'original_post_id' => $originalPost->id,
                    ]),
                ]);

                // Update pool entry
                $entry->update([
                    'repost_count' => $entry->repost_count + 1,
                    'next_repost_at' => Carbon::now()->addDays($rule->repost_interval_days),
                ]);

                // Update rule timestamp
                $rule->update(['last_reposted_at' => now()]);

                $count++;
            }
        }

        $this->log('Evergreen reposts processed', ['count' => $count]);

        return $count;
    }

    /**
     * Delete a rule and its pool.
     */
    public function deleteRule(EvergreenRule $rule): bool
    {
        return $this->transaction(function () use ($rule) {
            // Delete pool entries
            $rule->poolEntries()->delete();

            // Delete rule
            $deleted = $rule->delete();

            $this->log('Evergreen rule deleted', ['rule_id' => $rule->id]);

            return $deleted;
        });
    }
}
