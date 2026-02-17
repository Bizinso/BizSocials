<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inbox\InboxConversation;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxConversation>
 */
final class InboxConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<InboxConversation>
     */
    protected $model = InboxConversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $participantName = fake()->name();
        $participantUsername = fake()->userName();

        return [
            'workspace_id' => Workspace::factory(),
            'social_account_id' => SocialAccount::factory(),
            'conversation_key' => 'participant:' . strtolower($participantUsername),
            'subject' => "Conversation with {$participantName}",
            'participant_name' => $participantName,
            'participant_username' => $participantUsername,
            'participant_profile_url' => fake()->url(),
            'participant_avatar_url' => fake()->imageUrl(),
            'message_count' => fake()->numberBetween(1, 50),
            'first_message_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'last_message_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'status' => fake()->randomElement(['active', 'resolved', 'archived']),
            'metadata' => [
                'created_from_item_id' => fake()->uuid(),
            ],
        ];
    }

    /**
     * Indicate that the conversation is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the conversation is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'resolved',
        ]);
    }

    /**
     * Indicate that the conversation is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'archived',
        ]);
    }

    /**
     * Indicate that the conversation is post-based.
     */
    public function postBased(): static
    {
        return $this->state(fn (array $attributes): array => [
            'conversation_key' => 'post:' . fake()->uuid(),
            'subject' => 'Comments on post',
        ]);
    }

    /**
     * Indicate that the conversation is thread-based.
     */
    public function threadBased(): static
    {
        return $this->state(fn (array $attributes): array => [
            'conversation_key' => 'thread:' . fake()->uuid(),
        ]);
    }
}
