<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Enums\Tenant\CompanySize;
use App\Enums\Tenant\VerificationStatus;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantProfile;
use Illuminate\Database\Seeder;

/**
 * Seeder for TenantProfile model.
 *
 * Creates business profiles for tenants that require them.
 */
final class TenantProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = [
            // Acme Corporation
            [
                'tenant_slug' => 'acme-corporation',
                'legal_name' => 'Acme Corporation Pvt. Ltd.',
                'business_type' => 'Private Limited',
                'industry' => 'Technology',
                'company_size' => CompanySize::ENTERPRISE,
                'website' => 'https://acme-corp.example.com',
                'address_line1' => '123 Tech Park',
                'address_line2' => 'Building A, Floor 5',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'country' => 'IN',
                'postal_code' => '560001',
                'phone' => '+91-80-12345678',
                'gstin' => '29AABCU9603R1ZM',
                'pan' => 'AABCU9603R',
                'tax_id' => null,
                'verification_status' => VerificationStatus::VERIFIED,
                'verified_at' => now()->subDays(55),
            ],
            // StartupXYZ
            [
                'tenant_slug' => 'startupxyz',
                'legal_name' => 'StartupXYZ Technologies LLP',
                'business_type' => 'LLP',
                'industry' => 'Technology',
                'company_size' => CompanySize::SMALL,
                'website' => 'https://startupxyz.example.com',
                'address_line1' => '456 Startup Hub',
                'address_line2' => null,
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'IN',
                'postal_code' => '400001',
                'phone' => '+91-22-87654321',
                'gstin' => '27AADFS1234A1ZQ',
                'pan' => 'AADFS1234A',
                'tax_id' => null,
                'verification_status' => VerificationStatus::PENDING,
                'verified_at' => null,
            ],
            // Fashion Brand Co
            [
                'tenant_slug' => 'fashion-brand-co',
                'legal_name' => 'Fashion Brand Co Inc.',
                'business_type' => 'Corporation',
                'industry' => 'Fashion & Apparel',
                'company_size' => CompanySize::LARGE,
                'website' => 'https://fashionbrand.example.com',
                'address_line1' => '789 Fashion Avenue',
                'address_line2' => 'Suite 1200',
                'city' => 'New York',
                'state' => 'New York',
                'country' => 'US',
                'postal_code' => '10001',
                'phone' => '+1-212-555-1234',
                'gstin' => null,
                'pan' => null,
                'tax_id' => 'TAX-US-12345678',
                'verification_status' => VerificationStatus::VERIFIED,
                'verified_at' => now()->subDays(40),
            ],
            // Green Earth Foundation
            [
                'tenant_slug' => 'green-earth-foundation',
                'legal_name' => 'Green Earth Foundation',
                'business_type' => 'Section 8 Company',
                'industry' => 'Non-Profit',
                'company_size' => CompanySize::MEDIUM,
                'website' => 'https://greenearth.example.org',
                'address_line1' => '321 Environment Lane',
                'address_line2' => null,
                'city' => 'Delhi',
                'state' => 'Delhi',
                'country' => 'IN',
                'postal_code' => '110001',
                'phone' => '+91-11-23456789',
                'gstin' => '07AABCG5678H1ZI',
                'pan' => 'AABCG5678H',
                'tax_id' => null,
                'verification_status' => VerificationStatus::VERIFIED,
                'verified_at' => now()->subDays(85),
            ],
            // Suspended Inc
            [
                'tenant_slug' => 'suspended-inc',
                'legal_name' => 'Suspended Incorporated',
                'business_type' => 'Corporation',
                'industry' => 'Finance',
                'company_size' => CompanySize::ENTERPRISE,
                'website' => 'https://suspended-inc.example.com',
                'address_line1' => '555 Wall Street',
                'address_line2' => 'Floor 30',
                'city' => 'New York',
                'state' => 'New York',
                'country' => 'US',
                'postal_code' => '10005',
                'phone' => '+1-212-555-9999',
                'gstin' => null,
                'pan' => null,
                'tax_id' => 'TAX-US-98765432',
                'verification_status' => VerificationStatus::VERIFIED,
                'verified_at' => now()->subDays(115),
            ],
            // Pending Corp
            [
                'tenant_slug' => 'pending-corp',
                'legal_name' => 'Pending Corporation',
                'business_type' => 'Private Limited',
                'industry' => 'Professional Services',
                'company_size' => CompanySize::SMALL,
                'website' => null,
                'address_line1' => null,
                'address_line2' => null,
                'city' => null,
                'state' => null,
                'country' => 'IN',
                'postal_code' => null,
                'phone' => null,
                'gstin' => null,
                'pan' => null,
                'tax_id' => null,
                'verification_status' => VerificationStatus::PENDING,
                'verified_at' => null,
            ],
        ];

        foreach ($profiles as $profileData) {
            $tenantSlug = $profileData['tenant_slug'];
            unset($profileData['tenant_slug']);

            $tenant = Tenant::where('slug', $tenantSlug)->first();

            if ($tenant) {
                TenantProfile::firstOrCreate(
                    ['tenant_id' => $tenant->id],
                    $profileData
                );
            }
        }

        $this->command->info('Tenant profiles seeded successfully.');
    }
}
