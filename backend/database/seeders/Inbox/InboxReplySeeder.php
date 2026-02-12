<?php

declare(strict_types=1);

namespace Database\Seeders\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for InboxReply model.
 *
 * Creates sample replies for inbox items that can be replied to.
 */
final class InboxReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get comment inbox items that can be replied to (READ or RESOLVED)
        $replyableItems = InboxItem::where('item_type', InboxItemType::COMMENT)
            ->whereIn('status', [InboxItemStatus::READ, InboxItemStatus::RESOLVED])
            ->get();

        if ($replyableItems->isEmpty()) {
            $this->command->warn('No replyable inbox items found. Skipping inbox replies seeding.');

            return;
        }

        $users = User::all();

        foreach ($replyableItems as $item) {
            // 70% chance to have a reply
            if (!fake()->boolean(70)) {
                continue;
            }

            $replier = $item->assigned_to_user_id
                ? User::find($item->assigned_to_user_id)
                : $users->random();

            // Create 1-2 replies per item
            $replyCount = fake()->boolean(80) ? 1 : 2;

            for ($i = 0; $i < $replyCount; $i++) {
                $this->createReply($item, $replier);
            }
        }

        $this->command->info('Inbox replies seeded successfully.');
    }

    /**
     * Create a reply for an inbox item.
     */
    private function createReply(InboxItem $item, User $replier): InboxReply
    {
        // 90% success rate
        $isSuccessful = fake()->boolean(90);

        $sentAt = fake()->dateTimeBetween('-1 week', 'now');

        return InboxReply::create([
            'inbox_item_id' => $item->id,
            'replied_by_user_id' => $replier->id,
            'content_text' => $this->generateReplyContent(),
            'platform_reply_id' => $isSuccessful ? 'reply_' . fake()->uuid() : null,
            'sent_at' => $sentAt,
            'failed_at' => $isSuccessful ? null : $sentAt,
            'failure_reason' => $isSuccessful ? null : fake()->randomElement([
                'API rate limit exceeded',
                'Authentication token expired',
                'Network timeout',
            ]),
        ]);
    }

    /**
     * Generate realistic reply content.
     */
    private function generateReplyContent(): string
    {
        $replies = [
            'Thank you for your comment! We appreciate your engagement.',
            'Thanks for the feedback! We\'re glad you found this helpful.',
            'Great question! Let me clarify...',
            'Thank you! We\'re working on more content like this.',
            'We appreciate your support! Stay tuned for more updates.',
            'Thanks for reaching out! Feel free to DM us if you have more questions.',
            'Glad this resonated with you! Thanks for sharing.',
            'Thank you for your kind words! It means a lot to our team.',
            'Great point! We\'ll definitely consider this for future content.',
            'Thanks for engaging with our post! We love hearing from our community.',
        ];

        return fake()->randomElement($replies);
    }
}
