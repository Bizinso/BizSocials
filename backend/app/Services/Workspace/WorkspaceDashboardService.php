<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Enums\Content\PostStatus;
use App\Enums\Inbox\InboxItemStatus;
use App\Models\Content\Post;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;

final class WorkspaceDashboardService extends BaseService
{
    /**
     * Get dashboard statistics for a workspace.
     *
     * @return array<string, mixed>
     */
    public function getStats(Workspace $workspace): array
    {
        $workspaceId = $workspace->id;

        $totalPosts = Post::forWorkspace($workspaceId)->count();
        $postsPublished = Post::forWorkspace($workspaceId)->withStatus(PostStatus::PUBLISHED)->count();
        $postsScheduled = Post::forWorkspace($workspaceId)->withStatus(PostStatus::SCHEDULED)->count();
        $postsDraft = Post::forWorkspace($workspaceId)->withStatus(PostStatus::DRAFT)->count();
        $pendingApprovals = Post::forWorkspace($workspaceId)->withStatus(PostStatus::SUBMITTED)->count();

        $socialAccountsCount = SocialAccount::where('workspace_id', $workspaceId)->count();
        $inboxUnreadCount = InboxItem::where('workspace_id', $workspaceId)
            ->where('status', InboxItemStatus::UNREAD)
            ->count();
        $memberCount = $workspace->getMemberCount();

        $recentPosts = Post::forWorkspace($workspaceId)
            ->select(['id', 'content_text', 'status', 'scheduled_at', 'published_at', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'content_excerpt' => $post->content_text
                    ? mb_substr($post->content_text, 0, 100)
                    : null,
                'status' => $post->status->value,
                'scheduled_at' => $post->scheduled_at?->toIso8601String(),
                'published_at' => $post->published_at?->toIso8601String(),
                'created_at' => $post->created_at->toIso8601String(),
            ])
            ->toArray();

        return [
            'total_posts' => $totalPosts,
            'posts_published' => $postsPublished,
            'posts_scheduled' => $postsScheduled,
            'posts_draft' => $postsDraft,
            'pending_approvals' => $pendingApprovals,
            'social_accounts_count' => $socialAccountsCount,
            'inbox_unread_count' => $inboxUnreadCount,
            'member_count' => $memberCount,
            'recent_posts' => $recentPosts,
        ];
    }
}
