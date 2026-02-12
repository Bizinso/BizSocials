<?php

declare(strict_types=1);

namespace Database\Seeders\Billing;

use App\Enums\Billing\PaymentMethodType;
use App\Models\Billing\Payment;
use App\Models\Billing\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Seeder for PaymentMethod model.
 *
 * Creates default payment methods for tenants with successful payments.
 */
final class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get unique tenant IDs that have made payments
        $tenantIds = Payment::distinct('tenant_id')
            ->pluck('tenant_id')
            ->unique();

        foreach ($tenantIds as $tenantId) {
            // Get the most recent payment for this tenant to use as template
            $lastPayment = Payment::where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastPayment === null) {
                continue;
            }

            $type = $this->mapMethodToType($lastPayment->method);
            $details = $this->getDetails($type, $lastPayment->method_details ?? []);
            $expiresAt = $this->getExpiresAt($type, $details);

            PaymentMethod::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'is_default' => true,
                ],
                [
                    'razorpay_token_id' => 'token_' . fake()->regexify('[A-Za-z0-9]{14}'),
                    'details' => $details,
                    'expires_at' => $expiresAt,
                ]
            );
        }

        $this->command->info('Payment methods seeded successfully.');
    }

    /**
     * Map payment method string to PaymentMethodType enum.
     */
    private function mapMethodToType(?string $method): PaymentMethodType
    {
        return match ($method) {
            'card' => PaymentMethodType::CARD,
            'upi' => PaymentMethodType::UPI,
            'netbanking' => PaymentMethodType::NETBANKING,
            'wallet' => PaymentMethodType::WALLET,
            'emandate' => PaymentMethodType::EMANDATE,
            default => PaymentMethodType::CARD,
        };
    }

    /**
     * Get payment method details based on type.
     *
     * @param  array<string, mixed>  $existingDetails
     * @return array<string, mixed>
     */
    private function getDetails(PaymentMethodType $type, array $existingDetails): array
    {
        return match ($type) {
            PaymentMethodType::CARD => [
                'last4' => $existingDetails['last4'] ?? fake()->numerify('####'),
                'brand' => $existingDetails['brand'] ?? fake()->randomElement(['Visa', 'Mastercard', 'RuPay']),
                'exp_month' => $existingDetails['exp_month'] ?? fake()->numberBetween(1, 12),
                'exp_year' => $existingDetails['exp_year'] ?? fake()->numberBetween(2025, 2030),
                'name' => fake()->name(),
            ],
            PaymentMethodType::UPI => [
                'vpa' => $existingDetails['vpa'] ?? fake()->userName() . '@upi',
            ],
            PaymentMethodType::NETBANKING => [
                'bank' => $existingDetails['bank'] ?? fake()->randomElement(['HDFC', 'ICICI', 'SBI', 'Axis']),
            ],
            PaymentMethodType::WALLET => [
                'provider' => fake()->randomElement(['PayTM', 'PhonePe', 'Amazon Pay']),
            ],
            PaymentMethodType::EMANDATE => [
                'bank' => fake()->randomElement(['HDFC', 'ICICI', 'SBI', 'Axis']),
                'account_last4' => fake()->numerify('####'),
            ],
        };
    }

    /**
     * Get expiry date for payment method.
     *
     * @param  array<string, mixed>  $details
     */
    private function getExpiresAt(PaymentMethodType $type, array $details): ?\Carbon\Carbon
    {
        if ($type !== PaymentMethodType::CARD) {
            return null;
        }

        $expYear = $details['exp_year'] ?? now()->addYears(3)->year;
        $expMonth = $details['exp_month'] ?? 12;

        return now()->setYear((int) $expYear)->setMonth((int) $expMonth)->endOfMonth();
    }
}
