<?php

declare(strict_types=1);

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\WhatsAppPhoneStatus;
use App\Enums\WhatsApp\WhatsAppQualityRating;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for WhatsAppPhoneNumber model.
 *
 * @extends Factory<WhatsAppPhoneNumber>
 */
final class WhatsAppPhoneNumberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WhatsAppPhoneNumber>
     */
    protected $model = WhatsAppPhoneNumber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phoneNumber = '+1' . fake()->numerify('##########');
        
        return [
            'whatsapp_business_account_id' => WhatsAppBusinessAccount::factory(),
            'phone_number_id' => 'phone_' . fake()->unique()->numerify('##########'),
            'phone_number' => $phoneNumber,
            'display_name' => fake()->company(),
            'verified_name' => fake()->boolean(80) ? fake()->company() : null,
            'quality_rating' => fake()->randomElement([
                WhatsAppQualityRating::GREEN,
                WhatsAppQualityRating::GREEN,
                WhatsAppQualityRating::GREEN, // Weight toward green
                WhatsAppQualityRating::YELLOW,
            ]),
            'status' => fake()->randomElement([
                WhatsAppPhoneStatus::ACTIVE,
                WhatsAppPhoneStatus::ACTIVE,
                WhatsAppPhoneStatus::ACTIVE, // Weight toward active
                WhatsAppPhoneStatus::FLAGGED,
            ]),
            'is_primary' => false,
            'category' => fake()->randomElement(['Business', 'Service', 'Support', 'Sales']),
            'description' => fake()->boolean(70) ? fake()->sentence() : null,
            'address' => fake()->boolean(60) ? fake()->address() : null,
            'website' => fake()->boolean(60) ? fake()->url() : null,
            'support_email' => fake()->boolean(70) ? fake()->safeEmail() : null,
            'profile_picture_url' => null,
            'daily_send_count' => 0,
            'daily_send_limit' => fake()->randomElement([250, 1000, 10000, 100000]),
            'metadata' => [],
        ];
    }

    /**
     * Set phone number as primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => true,
        ]);
    }

    /**
     * Set phone number as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppPhoneStatus::ACTIVE,
        ]);
    }

    /**
     * Associate with a specific business account.
     */
    public function forBusinessAccount(WhatsAppBusinessAccount $account): static
    {
        return $this->state(fn (array $attributes): array => [
            'whatsapp_business_account_id' => $account->id,
        ]);
    }
}
