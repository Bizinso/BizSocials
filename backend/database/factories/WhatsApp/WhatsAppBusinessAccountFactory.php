<?php

declare(strict_types=1);

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Enums\WhatsApp\WhatsAppMessagingTier;
use App\Enums\WhatsApp\WhatsAppQualityRating;
use App\Models\Tenant\Tenant;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for WhatsAppBusinessAccount model.
 *
 * @extends Factory<WhatsAppBusinessAccount>
 */
final class WhatsAppBusinessAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WhatsAppBusinessAccount>
     */
    protected $model = WhatsAppBusinessAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'meta_business_account_id' => 'mba_' . fake()->unique()->numerify('##########'),
            'waba_id' => 'waba_' . fake()->unique()->numerify('##########'),
            'name' => fake()->company() . ' WhatsApp Business',
            'status' => fake()->randomElement([
                WhatsAppAccountStatus::VERIFIED,
                WhatsAppAccountStatus::VERIFIED,
                WhatsAppAccountStatus::VERIFIED, // Weight toward verified
                WhatsAppAccountStatus::PENDING_VERIFICATION,
            ]),
            'quality_rating' => fake()->randomElement([
                WhatsAppQualityRating::GREEN,
                WhatsAppQualityRating::GREEN,
                WhatsAppQualityRating::GREEN, // Weight toward green
                WhatsAppQualityRating::YELLOW,
            ]),
            'messaging_limit_tier' => fake()->randomElement([
                WhatsAppMessagingTier::TIER_1K,
                WhatsAppMessagingTier::TIER_10K,
                WhatsAppMessagingTier::TIER_100K,
            ]),
            'access_token_encrypted' => 'test_token_' . fake()->uuid(),
            'webhook_verify_token' => fake()->uuid(),
            'webhook_subscribed_fields' => ['messages', 'message_status'],
            'compliance_accepted_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-1 year') : null,
            'compliance_accepted_by_user_id' => null,
            'is_marketing_enabled' => fake()->boolean(70),
            'suspended_reason' => null,
            'metadata' => [],
        ];
    }

    /**
     * Set account status to verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppAccountStatus::VERIFIED,
            'quality_rating' => WhatsAppQualityRating::GREEN,
        ]);
    }

    /**
     * Set account status to suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppAccountStatus::SUSPENDED,
            'suspended_reason' => 'Policy violation',
        ]);
    }

    /**
     * Associate with a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
