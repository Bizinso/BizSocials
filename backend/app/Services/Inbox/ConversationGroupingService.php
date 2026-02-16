<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Models\Inbox\InboxConversation;
use App\Models\Inbox\InboxItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ConversationGroupingService
 *
 * Implements conversation threading logic to group related inbox items
 * into conversation threads. Uses multiple detection algorithms to
 * identify messages that belong to the same conversation.
 */
final class ConversationGroupingService
{
    /**
     * Group an inbox item into a conversation.
     *
     * This method detects which conversation the item belongs to
     * and creates a new conversation if needed.
     */
    public function groupIntoConversation(InboxItem $item): InboxConversation
    {
        // Try to find existing conversation using thread detection
        $conversation = $this->detectExistingConversation($item);

        if ($conversation === null) {
            // Create new conversation
            $conversation = $this->createConversation($item);
        }

        // Link item to conversation
        $item->conversation_id = $conversation->id;
        $item->save();

        // Update conversation metadata
        $conversation->addMessage($item);

        Log::info('Inbox item grouped into conversation', [
            'item_id' => $item->id,
            'conversation_id' => $conversation->id,
            'is_new_conversation' => $conversation->wasRecentlyCreated,
        ]);

        return $conversation;
    }

    /**
     * Detect existing conversation for an inbox item.
     *
     * Uses multiple detection algorithms:
     * 1. Platform thread ID (if available in metadata)
     * 2. Post-based grouping (comments on same post)
     * 3. Participant-based grouping (messages from same author)
     */
    private function detectExistingConversation(InboxItem $item): ?InboxConversation
    {
        // Algorithm 1: Platform thread ID
        $conversation = $this->detectByPlatformThreadId($item);
        if ($conversation !== null) {
            return $conversation;
        }

        // Algorithm 2: Post-based grouping
        $conversation = $this->detectByPost($item);
        if ($conversation !== null) {
            return $conversation;
        }

        // Algorithm 3: Participant-based grouping
        $conversation = $this->detectByParticipant($item);
        if ($conversation !== null) {
            return $conversation;
        }

        return null;
    }

    /**
     * Detect conversation by platform thread ID.
     *
     * Some platforms provide explicit thread/conversation IDs
     * in their API responses. Use these when available.
     */
    private function detectByPlatformThreadId(InboxItem $item): ?InboxConversation
    {
        // Check if metadata contains a thread ID
        $threadId = $item->metadata['thread_id'] ?? null;
        
        if ($threadId === null) {
            return null;
        }

        $conversationKey = "thread:{$threadId}";

        return InboxConversation::where('workspace_id', $item->workspace_id)
            ->where('social_account_id', $item->social_account_id)
            ->where('conversation_key', $conversationKey)
            ->first();
    }

    /**
     * Detect conversation by post.
     *
     * Group all comments on the same post into one conversation.
     * This is useful for managing engagement on a specific post.
     */
    private function detectByPost(InboxItem $item): ?InboxConversation
    {
        // Only applicable if item is related to a post
        if ($item->post_target_id === null && $item->platform_post_id === null) {
            return null;
        }

        // Build conversation key based on post
        $postIdentifier = $item->post_target_id ?? $item->platform_post_id;
        $conversationKey = "post:{$postIdentifier}";

        return InboxConversation::where('workspace_id', $item->workspace_id)
            ->where('social_account_id', $item->social_account_id)
            ->where('conversation_key', $conversationKey)
            ->first();
    }

    /**
     * Detect conversation by participant.
     *
     * Group messages from the same author into one conversation.
     * This creates a direct message-style thread with each unique participant.
     */
    private function detectByParticipant(InboxItem $item): ?InboxConversation
    {
        // Use author username if available, otherwise use author name
        $participantIdentifier = $item->author_username ?? $item->author_name;
        
        // Normalize identifier (lowercase, trim)
        $participantIdentifier = strtolower(trim($participantIdentifier));
        
        $conversationKey = "participant:{$participantIdentifier}";

        return InboxConversation::where('workspace_id', $item->workspace_id)
            ->where('social_account_id', $item->social_account_id)
            ->where('conversation_key', $conversationKey)
            ->first();
    }

    /**
     * Create a new conversation for an inbox item.
     */
    private function createConversation(InboxItem $item): InboxConversation
    {
        // Determine conversation key based on available data
        $conversationKey = $this->generateConversationKey($item);

        // Determine subject
        $subject = $this->generateSubject($item);

        return InboxConversation::create([
            'workspace_id' => $item->workspace_id,
            'social_account_id' => $item->social_account_id,
            'conversation_key' => $conversationKey,
            'subject' => $subject,
            'participant_name' => $item->author_name,
            'participant_username' => $item->author_username,
            'participant_profile_url' => $item->author_profile_url,
            'participant_avatar_url' => $item->author_avatar_url,
            'message_count' => 0,
            'first_message_at' => $item->platform_created_at,
            'last_message_at' => $item->platform_created_at,
            'status' => 'active',
            'metadata' => [
                'created_from_item_id' => $item->id,
                'item_type' => $item->item_type->value,
            ],
        ]);
    }

    /**
     * Generate conversation key for a new conversation.
     */
    private function generateConversationKey(InboxItem $item): string
    {
        // Check for platform thread ID
        $threadId = $item->metadata['thread_id'] ?? null;
        if ($threadId !== null) {
            return "thread:{$threadId}";
        }

        // Check for post-based grouping
        if ($item->post_target_id !== null) {
            return "post:{$item->post_target_id}";
        }
        
        if ($item->platform_post_id !== null) {
            return "post:{$item->platform_post_id}";
        }

        // Default to participant-based grouping
        $participantIdentifier = $item->author_username ?? $item->author_name;
        $participantIdentifier = strtolower(trim($participantIdentifier));
        
        return "participant:{$participantIdentifier}";
    }

    /**
     * Generate subject for a new conversation.
     */
    private function generateSubject(InboxItem $item): string
    {
        // If it's a comment on a post, use post context
        if ($item->post_target_id !== null || $item->platform_post_id !== null) {
            return "Comments on post";
        }

        // For mentions or direct messages, use participant name
        return "Conversation with {$item->author_name}";
    }

    /**
     * Regroup all inbox items in a workspace.
     *
     * This is useful for migrating existing data or fixing
     * conversation groupings after algorithm changes.
     */
    public function regroupAllItems(string $workspaceId): array
    {
        $stats = [
            'total_items' => 0,
            'grouped_items' => 0,
            'new_conversations' => 0,
            'errors' => 0,
        ];

        DB::transaction(function () use ($workspaceId, &$stats): void {
            // Get all items without conversations
            $items = InboxItem::where('workspace_id', $workspaceId)
                ->whereNull('conversation_id')
                ->orderBy('platform_created_at')
                ->get();

            $stats['total_items'] = $items->count();

            foreach ($items as $item) {
                try {
                    $conversation = $this->groupIntoConversation($item);
                    
                    $stats['grouped_items']++;
                    
                    if ($conversation->wasRecentlyCreated) {
                        $stats['new_conversations']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Failed to group inbox item', [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $stats;
    }

    /**
     * Get conversation statistics for a workspace.
     */
    public function getConversationStats(string $workspaceId): array
    {
        return [
            'total_conversations' => InboxConversation::where('workspace_id', $workspaceId)->count(),
            'active_conversations' => InboxConversation::where('workspace_id', $workspaceId)
                ->where('status', 'active')
                ->count(),
            'resolved_conversations' => InboxConversation::where('workspace_id', $workspaceId)
                ->where('status', 'resolved')
                ->count(),
            'archived_conversations' => InboxConversation::where('workspace_id', $workspaceId)
                ->where('status', 'archived')
                ->count(),
            'items_without_conversation' => InboxItem::where('workspace_id', $workspaceId)
                ->whereNull('conversation_id')
                ->count(),
        ];
    }
}
