<?php

declare(strict_types=1);

namespace Database\Seeders\Inbox;

use App\Enums\Content\PostTargetStatus;
use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Models\Content\PostTarget;
use App\Models\Inbox\InboxItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for InboxItem model.
 *
 * Creates sample inbox items (comments and mentions) for published posts.
 */
final class InboxItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get published post targets
        $publishedTargets = PostTarget::with(['post.workspace', 'socialAccount'])
            ->where('status', PostTargetStatus::PUBLISHED)
            ->get();

        if ($publishedTargets->isEmpty()) {
            $this->command->warn('No published posts found. Skipping inbox items seeding.');

            return;
        }

        $users = User::all();

        foreach ($publishedTargets as $target) {
            $workspace = $target->post?->workspace;
            $socialAccount = $target->socialAccount;

            if (!$workspace || !$socialAccount) {
                continue;
            }

            // Create 2-5 comments per published post
            $commentCount = random_int(2, 5);
            for ($i = 0; $i < $commentCount; $i++) {
                $this->createInboxItem(
                    workspace: $workspace,
                    socialAccount: $socialAccount,
                    postTarget: $target,
                    type: InboxItemType::COMMENT,
                    users: $users
                );
            }

            // Create 0-2 mentions related to the social account
            $mentionCount = random_int(0, 2);
            for ($i = 0; $i < $mentionCount; $i++) {
                $this->createInboxItem(
                    workspace: $workspace,
                    socialAccount: $socialAccount,
                    postTarget: null,
                    type: InboxItemType::MENTION,
                    users: $users
                );
            }
        }

        $this->command->info('Inbox items seeded successfully.');
    }

    /**
     * Create an inbox item.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>  $users
     */
    private function createInboxItem(
        mixed $workspace,
        mixed $socialAccount,
        ?PostTarget $postTarget,
        InboxItemType $type,
        $users
    ): InboxItem {
        // Generate author info
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $username = strtolower($firstName) . '_' . strtolower($lastName) . fake()->randomNumber(3);

        // Randomly select status
        $status = fake()->randomElement([
            InboxItemStatus::UNREAD,
            InboxItemStatus::UNREAD,
            InboxItemStatus::READ,
            InboxItemStatus::READ,
            InboxItemStatus::RESOLVED,
        ]);

        // Determine if assigned/resolved
        $assignedUser = $status !== InboxItemStatus::UNREAD && fake()->boolean(50)
            ? $users->random()
            : null;

        $resolvedUser = $status === InboxItemStatus::RESOLVED
            ? ($assignedUser ?? $users->random())
            : null;

        // Generate content based on type
        $content = $type === InboxItemType::COMMENT
            ? $this->generateCommentContent()
            : $this->generateMentionContent($socialAccount->platform_username ?? 'account');

        return InboxItem::create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'post_target_id' => $postTarget?->id,
            'item_type' => $type,
            'status' => $status,
            'platform_item_id' => 'plt_' . fake()->uuid(),
            'platform_post_id' => $postTarget?->external_post_id ?? ($type === InboxItemType::MENTION ? 'post_' . fake()->uuid() : null),
            'author_name' => $firstName . ' ' . $lastName,
            'author_username' => $username,
            'author_profile_url' => 'https://linkedin.com/in/' . $username,
            'author_avatar_url' => 'https://i.pravatar.cc/150?u=' . $username,
            'content_text' => $content,
            'platform_created_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'assigned_to_user_id' => $assignedUser?->id,
            'assigned_at' => $assignedUser ? fake()->dateTimeBetween('-1 week', 'now') : null,
            'resolved_at' => $resolvedUser ? fake()->dateTimeBetween('-1 week', 'now') : null,
            'resolved_by_user_id' => $resolvedUser?->id,
        ]);
    }

    /**
     * Generate realistic comment content.
     */
    private function generateCommentContent(): string
    {
        $comments = [
            'Great post! Really insightful.',
            'Thanks for sharing this! Very helpful.',
            'I completely agree with this perspective.',
            'This is exactly what I needed to read today.',
            'Interesting take on this topic. Would love to hear more.',
            'Well said! Couldn\'t agree more.',
            'This resonates with our experience as well.',
            'Love this approach! We\'ve implemented something similar.',
            'Question: How do you handle [specific aspect]?',
            'Great insights! Following for more content like this.',
            'Congratulations on this achievement!',
            'This is inspiring! Keep up the great work.',
        ];

        return fake()->randomElement($comments);
    }

    /**
     * Generate realistic mention content.
     */
    private function generateMentionContent(string $username): string
    {
        $mentions = [
            "Hey @{$username}, thought you might find this interesting!",
            "Shoutout to @{$username} for their amazing work!",
            "@{$username} What do you think about this?",
            "Totally agree with @{$username}'s recent post on this topic.",
            "Great collaboration with @{$username} on this project!",
            "Thanks @{$username} for the recommendation!",
        ];

        return fake()->randomElement($mentions);
    }
}
