<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Enums\Billing\PaymentMethodType;
use App\Models\Billing\PaymentMethod;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PaymentMethod model.
 *
 * @extends Factory<PaymentMethod>
 */
final class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PaymentMethod>
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate future expiration date (1-5 years from now)
        $expYear = now()->addYears(fake()->numberBetween(1, 5))->year;
        $expMonth = fake()->numberBetween(1, 12);

        return [
            'tenant_id' => Tenant::factory(),
            'razorpay_token_id' => 'token_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'type' => PaymentMethodType::CARD,
            'is_default' => false,
            'details' => [
                'last4' => fake()->numerify('####'),
                'brand' => fake()->randomElement(['Visa', 'Mastercard', 'RuPay']),
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'name' => fake()->name(),
            ],
            'expires_at' => now()->setYear($expYear)->setMonth($expMonth)->endOfMonth(),
        ];
    }

    /**
     * Set type to card.
     */
    public function card(): static
    {
        // Generate future expiration date (1-5 years from now)
        $expYear = now()->addYears(fake()->numberBetween(1, 5))->year;
        $expMonth = fake()->numberBetween(1, 12);

        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::CARD,
            'details' => [
                'last4' => fake()->numerify('####'),
                'brand' => fake()->randomElement(['Visa', 'Mastercard', 'RuPay']),
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'name' => fake()->name(),
            ],
            'expires_at' => now()->setYear($expYear)->setMonth($expMonth)->endOfMonth(),
        ]);
    }

    /**
     * Set type to UPI.
     */
    public function upi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::UPI,
            'details' => [
                'vpa' => fake()->userName() . '@upi',
            ],
            'expires_at' => null,
        ]);
    }

    /**
     * Set type to netbanking.
     */
    public function netbanking(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::NETBANKING,
            'details' => [
                'bank' => fake()->randomElement(['HDFC', 'ICICI', 'SBI', 'Axis']),
            ],
            'expires_at' => null,
        ]);
    }

    /**
     * Set type to wallet.
     */
    public function wallet(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::WALLET,
            'details' => [
                'provider' => fake()->randomElement(['PayTM', 'PhonePe', 'Amazon Pay']),
            ],
            'expires_at' => null,
        ]);
    }

    /**
     * Set type to e-mandate.
     */
    public function emandate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::EMANDATE,
            'details' => [
                'bank' => fake()->randomElement(['HDFC', 'ICICI', 'SBI', 'Axis']),
                'account_last4' => fake()->numerify('####'),
            ],
            'expires_at' => null,
        ]);
    }

    /**
     * Set as default payment method.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * Set payment method as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PaymentMethodType::CARD,
            'expires_at' => now()->subMonths(fake()->numberBetween(1, 12)),
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
