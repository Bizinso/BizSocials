<?php

declare(strict_types=1);

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\WhatsAppTemplateCategory;
use App\Enums\WhatsApp\WhatsAppTemplateStatus;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for WhatsAppTemplate model.
 *
 * @extends Factory<WhatsAppTemplate>
 */
final class WhatsAppTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WhatsAppTemplate>
     */
    protected $model = WhatsAppTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = strtolower(str_replace(' ', '_', fake()->words(3, true)));
        
        return [
            'workspace_id' => Workspace::factory(),
            'whatsapp_phone_number_id' => WhatsAppPhoneNumber::factory(),
            'meta_template_id' => null,
            'name' => $name,
            'language' => 'en',
            'category' => fake()->randomElement([
                WhatsAppTemplateCategory::MARKETING,
                WhatsAppTemplateCategory::UTILITY,
                WhatsAppTemplateCategory::AUTHENTICATION,
            ]),
            'status' => WhatsAppTemplateStatus::DRAFT,
            'rejection_reason' => null,
            'header_type' => 'none',
            'header_content' => null,
            'body_text' => fake()->sentence(),
            'footer_text' => null,
            'buttons' => null,
            'sample_values' => null,
            'usage_count' => 0,
            'last_used_at' => null,
            'submitted_at' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Set template as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppTemplateStatus::APPROVED,
            'meta_template_id' => 'template_' . fake()->unique()->numerify('##########'),
            'submitted_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Set template as pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppTemplateStatus::PENDING_APPROVAL,
            'meta_template_id' => 'template_' . fake()->unique()->numerify('##########'),
            'submitted_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Set template as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppTemplateStatus::REJECTED,
            'meta_template_id' => 'template_' . fake()->unique()->numerify('##########'),
            'submitted_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Set template with text header.
     */
    public function withTextHeader(): static
    {
        return $this->state(fn (array $attributes): array => [
            'header_type' => 'text',
            'header_content' => fake()->sentence(3),
        ]);
    }

    /**
     * Set template with image header.
     */
    public function withImageHeader(): static
    {
        return $this->state(fn (array $attributes): array => [
            'header_type' => 'image',
            'header_content' => null,
        ]);
    }

    /**
     * Set template with footer.
     */
    public function withFooter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'footer_text' => fake()->sentence(4),
        ]);
    }

    /**
     * Set template with buttons.
     */
    public function withButtons(): static
    {
        return $this->state(fn (array $attributes): array => [
            'buttons' => [
                ['type' => 'QUICK_REPLY', 'text' => 'Yes'],
                ['type' => 'QUICK_REPLY', 'text' => 'No'],
            ],
        ]);
    }
}
