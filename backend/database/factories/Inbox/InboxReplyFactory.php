<?php

declare(strict_types=1);

namespace Database\Factories\Inbox;

use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for InboxReply model.
 *
 * @extends Factory<InboxReply>
 */
final class InboxReplyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<InboxReply>
     */
    protected $model = InboxReply::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inbox_item_id' => InboxItem::factory(),
            'replied_by_user_id' => User::factory(),
            'content_text' => fake()->sentence(15),
            'platform_reply_id' => null,
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'failed_at' => null,
            'failure_reason' => null,
        ];
    }

    /**
     * Set the reply as sent successfully.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform_reply_id' => 'reply_' . fake()->uuid(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);
    }

    /**
     * Set the reply as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform_reply_id' => null,
            'failed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'failure_reason' => fake()->randomElement([
                'API rate limit exceeded',
                'Authentication token expired',
                'Content policy violation',
                'Network timeout',
                'Comment no longer exists',
            ]),
        ]);
    }

    /**
     * Associate with a specific inbox item.
     */
    public function forInboxItem(InboxItem $inboxItem): static
    {
        return $this->state(fn (array $attributes): array => [
            'inbox_item_id' => $inboxItem->id,
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'replied_by_user_id' => $user->id,
        ]);
    }

    /**
     * Set specific content.
     */
    public function withContent(string $content): static
    {
        return $this->state(fn (array $attributes): array => [
            'content_text' => $content,
        ]);
    }
}
