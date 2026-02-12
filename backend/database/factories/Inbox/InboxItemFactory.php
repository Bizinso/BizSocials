<?php

declare(strict_types=1);

namespace Database\Factories\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Models\Content\PostTarget;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for InboxItem model.
 *
 * @extends Factory<InboxItem>
 */
final class InboxItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<InboxItem>
     */
    protected $model = InboxItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $username = strtolower($firstName) . '_' . strtolower($lastName) . fake()->randomNumber(3);

        return [
            'workspace_id' => Workspace::factory(),
            'social_account_id' => SocialAccount::factory(),
            'post_target_id' => null,
            'item_type' => fake()->randomElement(InboxItemType::cases()),
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => 'plt_' . fake()->uuid(),
            'platform_post_id' => fake()->boolean(70) ? 'post_' . fake()->uuid() : null,
            'author_name' => $firstName . ' ' . $lastName,
            'author_username' => $username,
            'author_profile_url' => 'https://linkedin.com/in/' . $username,
            'author_avatar_url' => 'https://i.pravatar.cc/150?u=' . $username,
            'content_text' => fake()->sentence(10),
            'platform_created_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'assigned_to_user_id' => null,
            'assigned_at' => null,
            'resolved_at' => null,
            'resolved_by_user_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Set the item type to COMMENT.
     */
    public function comment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'item_type' => InboxItemType::COMMENT,
        ]);
    }

    /**
     * Set the item type to MENTION.
     */
    public function mention(): static
    {
        return $this->state(fn (array $attributes): array => [
            'item_type' => InboxItemType::MENTION,
        ]);
    }

    /**
     * Set the status to UNREAD.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InboxItemStatus::UNREAD,
        ]);
    }

    /**
     * Set the status to READ.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InboxItemStatus::READ,
        ]);
    }

    /**
     * Set the status to RESOLVED.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InboxItemStatus::RESOLVED,
            'resolved_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'resolved_by_user_id' => User::factory(),
        ]);
    }

    /**
     * Set the status to ARCHIVED.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InboxItemStatus::ARCHIVED,
        ]);
    }

    /**
     * Assign to a user.
     */
    public function assigned(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_to_user_id' => $user?->id ?? User::factory(),
            'assigned_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Associate with a specific social account.
     */
    public function forSocialAccount(SocialAccount $socialAccount): static
    {
        return $this->state(fn (array $attributes): array => [
            'social_account_id' => $socialAccount->id,
        ]);
    }

    /**
     * Associate with a specific post target.
     */
    public function forPostTarget(PostTarget $postTarget): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_target_id' => $postTarget->id,
            'item_type' => InboxItemType::COMMENT,
        ]);
    }

    /**
     * Set specific metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => $metadata,
        ]);
    }
}
