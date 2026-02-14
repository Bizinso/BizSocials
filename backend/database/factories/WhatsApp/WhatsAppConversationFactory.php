<?php

declare(strict_types=1);

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\WhatsAppConversationStatus;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for WhatsAppConversation model.
 *
 * @extends Factory<WhatsAppConversation>
 */
final class WhatsAppConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WhatsAppConversation>
     */
    protected $model = WhatsAppConversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customerPhone = '+1' . fake()->numerify('##########');
        $lastMessageAt = fake()->dateTimeBetween('-7 days');
        
        return [
            'workspace_id' => Workspace::factory(),
            'whatsapp_phone_number_id' => WhatsAppPhoneNumber::factory(),
            'customer_phone' => $customerPhone,
            'customer_name' => fake()->boolean(70) ? fake()->name() : null,
            'customer_profile_name' => fake()->boolean(80) ? fake()->name() : null,
            'status' => fake()->randomElement([
                WhatsAppConversationStatus::ACTIVE,
                WhatsAppConversationStatus::ACTIVE,
                WhatsAppConversationStatus::ACTIVE, // Weight toward active
                WhatsAppConversationStatus::PENDING,
                WhatsAppConversationStatus::RESOLVED,
            ]),
            'assigned_to_user_id' => null,
            'assigned_to_team' => null,
            'priority' => fake()->randomElement(['low', 'normal', 'high']),
            'last_message_at' => $lastMessageAt,
            'last_customer_message_at' => $lastMessageAt,
            'conversation_expires_at' => now()->addHours(24),
            'is_within_service_window' => true,
            'message_count' => fake()->numberBetween(1, 50),
            'tags' => fake()->boolean(40) ? fake()->randomElements(['support', 'sales', 'billing', 'urgent'], rand(1, 2)) : null,
            'internal_notes_count' => 0,
            'sla_breach_at' => null,
            'first_response_at' => fake()->boolean(70) ? fake()->dateTimeBetween($lastMessageAt) : null,
            'metadata' => [],
        ];
    }

    /**
     * Set conversation as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppConversationStatus::ACTIVE,
            'is_within_service_window' => true,
            'last_customer_message_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }

    /**
     * Set conversation as resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppConversationStatus::RESOLVED,
        ]);
    }

    /**
     * Set conversation outside service window.
     */
    public function outsideServiceWindow(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_within_service_window' => false,
            'last_customer_message_at' => now()->subHours(25),
            'conversation_expires_at' => now()->subHour(),
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
     * Associate with a specific phone number.
     */
    public function forPhoneNumber(WhatsAppPhoneNumber $phone): static
    {
        return $this->state(fn (array $attributes): array => [
            'whatsapp_phone_number_id' => $phone->id,
        ]);
    }
}
