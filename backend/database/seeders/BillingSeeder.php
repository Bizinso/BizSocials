<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Billing\InvoiceSeeder;
use Database\Seeders\Billing\PaymentMethodSeeder;
use Database\Seeders\Billing\PaymentSeeder;
use Database\Seeders\Billing\SubscriptionSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Billing domain.
 *
 * Calls all billing-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Billing seeders...');

        // 1. Subscriptions first (depends on tenants and plans)
        $this->call(SubscriptionSeeder::class);

        // 2. Invoices (depends on subscriptions)
        $this->call(InvoiceSeeder::class);

        // 3. Payments (depends on invoices)
        $this->call(PaymentSeeder::class);

        // 4. Payment Methods (depends on payments for tenant reference)
        $this->call(PaymentMethodSeeder::class);

        $this->command->info('Billing seeders completed successfully!');
    }
}
