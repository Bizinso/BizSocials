<?php

declare(strict_types=1);

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Enums\WhatsApp\WhatsAppMessageType;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for WhatsAppMessage model.
 *
 * @extends Factory<WhatsAppMessage>
 */
final class WhatsAppMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WhatsAppMessage>
     */
    protected $model = WhatsAppMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $direction = fake()->randomElement([
            WhatsAppMessageDirection::INBOUND,
            WhatsAppMessageDirection::OUTBOUND,
        ]);
        
        $type = fake()->randomElement([
            WhatsAppMessageType::TEXT,
            WhatsAppMessageType::TEXT,
            WhatsAppMessageType::TEXT, // Weight toward text
            WhatsAppMessageType::IMAGE,
            WhatsAppMessageType::VIDEO,
        ]);
        
        return [
            'conversation_id' => WhatsAppConversation::factory(),
            'wamid' => 'wamid.' . fake()->unique()->uuid(),
            'direction' => $direction,
            'type' => $type,
            'content_text' => $type === WhatsAppMessageType::TEXT ? fake()->sentence() : null,
            'content_payload' => null,
            'media_url' => in_array($type, [WhatsAppMessageType::IMAGE, WhatsAppMessageType::VIDEO]) 
                ? fake()->imageUrl() 
                : null,
            'media_mime_type' => match ($type) {
                WhatsAppMessageType::IMAGE => 'image/jpeg',
                WhatsAppMessageType::VIDEO => 'video/mp4',
                default => null,
            },
            'media_file_size' => in_array($type, [WhatsAppMessageType::IMAGE, WhatsAppMessageType::VIDEO])
                ? fake()->numberBetween(10000, 5000000)
                : null,
            'template_id' => null,
            'sent_by_user_id' => null,
            'status' => $direction === WhatsAppMessageDirection::INBOUND
                ? WhatsAppMessageStatus::DELIVERED
                : fake()->randomElement([
                    WhatsAppMessageStatus::SENT,
                    WhatsAppMessageStatus::DELIVERED,
                    WhatsAppMessageStatus::READ,
                ]),
            'status_updated_at' => now(),
            'error_code' => null,
            'error_message' => null,
            'platform_timestamp' => fake()->dateTimeBetween('-7 days'),
            'metadata' => [],
        ];
    }

    /**
     * Set message as inbound.
     */
    public function inbound(): static
    {
        return $this->state(fn (array $attributes): array => [
            'direction' => WhatsAppMessageDirection::INBOUND,
            'status' => WhatsAppMessageStatus::DELIVERED,
        ]);
    }

    /**
     * Set message as outbound.
     */
    public function outbound(): static
    {
        return $this->state(fn (array $attributes): array => [
            'direction' => WhatsAppMessageDirection::OUTBOUND,
            'status' => WhatsAppMessageStatus::SENT,
        ]);
    }

    /**
     * Set message type as text.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => WhatsAppMessageType::TEXT,
            'content_text' => fake()->sentence(),
            'media_url' => null,
            'media_mime_type' => null,
        ]);
    }

    /**
     * Set message type as image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => WhatsAppMessageType::IMAGE,
            'media_url' => fake()->imageUrl(),
            'media_mime_type' => 'image/jpeg',
            'content_text' => fake()->boolean(50) ? fake()->sentence() : null,
        ]);
    }

    /**
     * Set message status as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppMessageStatus::FAILED,
            'error_code' => '131047',
            'error_message' => 'Message failed to send',
        ]);
    }

    /**
     * Associate with a specific conversation.
     */
    public function forConversation(WhatsAppConversation $conversation): static
    {
        return $this->state(fn (array $attributes): array => [
            'conversation_id' => $conversation->id,
        ]);
    }
}
