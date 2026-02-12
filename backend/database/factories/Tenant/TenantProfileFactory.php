<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\CompanySize;
use App\Enums\Tenant\VerificationStatus;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for TenantProfile model.
 *
 * @extends Factory<TenantProfile>
 */
final class TenantProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TenantProfile>
     */
    protected $model = TenantProfile::class;

    /**
     * Industries list for realistic data.
     *
     * @var array<string>
     */
    private array $industries = [
        'Technology',
        'Healthcare',
        'Finance',
        'Education',
        'Retail',
        'Manufacturing',
        'Media & Entertainment',
        'Real Estate',
        'Hospitality',
        'Professional Services',
        'Non-Profit',
        'E-commerce',
        'Marketing & Advertising',
        'Food & Beverage',
        'Fashion & Apparel',
    ];

    /**
     * Business types list.
     *
     * @var array<string>
     */
    private array $businessTypes = [
        'Private Limited',
        'Public Limited',
        'LLP',
        'Sole Proprietorship',
        'Partnership',
        'One Person Company',
        'Section 8 Company',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'legal_name' => fake()->company() . ' ' . fake()->randomElement(['Pvt. Ltd.', 'LLP', 'Inc.']),
            'business_type' => fake()->randomElement($this->businessTypes),
            'industry' => fake()->randomElement($this->industries),
            'company_size' => fake()->randomElement(CompanySize::cases()),
            'website' => fake()->url(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->boolean(30) ? fake()->secondaryAddress() : null,
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'IN', // Default to India
            'postal_code' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'gstin' => null,
            'pan' => null,
            'tax_id' => null,
            'verification_status' => fake()->randomElement([
                VerificationStatus::PENDING,
                VerificationStatus::VERIFIED,
                VerificationStatus::VERIFIED, // Weight toward verified
            ]),
            'verified_at' => null,
        ];
    }

    /**
     * Configure verified_at based on verification_status.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (TenantProfile $profile): void {
            if ($profile->verification_status === VerificationStatus::VERIFIED && $profile->verified_at === null) {
                $profile->verified_at = fake()->dateTimeBetween('-30 days', 'now');
            }
        });
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

    /**
     * Set verification status to verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Set verification status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::PENDING,
            'verified_at' => null,
        ]);
    }

    /**
     * Set verification status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::FAILED,
            'verified_at' => null,
        ]);
    }

    /**
     * Add Indian tax information (GST and PAN).
     */
    public function withIndianTax(): static
    {
        return $this->state(fn (array $attributes): array => [
            'country' => 'IN',
            'gstin' => $this->generateGstin(),
            'pan' => $this->generatePan(),
            'tax_id' => null,
        ]);
    }

    /**
     * Add international tax ID.
     */
    public function withInternationalTax(string $country = 'US'): static
    {
        return $this->state(fn (array $attributes): array => [
            'country' => $country,
            'gstin' => null,
            'pan' => null,
            'tax_id' => fake()->numerify('TAX-###########'),
        ]);
    }

    /**
     * Set company size to solo.
     */
    public function solo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_size' => CompanySize::SOLO,
        ]);
    }

    /**
     * Set company size to small.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_size' => CompanySize::SMALL,
        ]);
    }

    /**
     * Set company size to medium.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_size' => CompanySize::MEDIUM,
        ]);
    }

    /**
     * Set company size to large.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_size' => CompanySize::LARGE,
        ]);
    }

    /**
     * Set company size to enterprise.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_size' => CompanySize::ENTERPRISE,
        ]);
    }

    /**
     * Generate a realistic-looking Indian GSTIN.
     */
    private function generateGstin(): string
    {
        $stateCode = str_pad((string) fake()->numberBetween(1, 37), 2, '0', STR_PAD_LEFT);
        $pan = $this->generatePan();
        $entityCode = fake()->randomDigit();
        $checkDigit = fake()->randomElement(['Z', 'A', 'B', 'C']);

        return $stateCode . $pan . $entityCode . $checkDigit;
    }

    /**
     * Generate a realistic-looking Indian PAN.
     */
    private function generatePan(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return fake()->randomElements(str_split($letters), 3, false) === []
            ? 'ABC'
            : implode('', fake()->randomElements(str_split($letters), 3))
            . 'P' // P for Person/Company
            . fake()->randomElement(str_split($letters))
            . fake()->numerify('####')
            . fake()->randomElement(str_split($letters));
    }
}
