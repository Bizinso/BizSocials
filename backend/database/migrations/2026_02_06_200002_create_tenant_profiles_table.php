<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for tenant_profiles table.
 *
 * Creates the table for storing detailed business profile information
 * for tenants. This includes legal name, address, tax information,
 * and verification status.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_profiles', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // One-to-one relationship with tenants
            $table->uuid('tenant_id')->unique();

            // Business information
            $table->string('legal_name')->nullable();
            $table->string('business_type', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('company_size', 20)->nullable(); // CompanySize enum
            $table->string('website')->nullable();

            // Address fields
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 2)->nullable(); // ISO 3166-1 alpha-2
            $table->string('postal_code', 20)->nullable();

            // Contact
            $table->string('phone', 20)->nullable();

            // Tax information (India-focused with international support)
            $table->string('gstin', 15)->nullable(); // India GST number
            $table->string('pan', 10)->nullable();    // India PAN
            $table->string('tax_id', 50)->nullable(); // International tax ID

            // Verification status (VerificationStatus enum)
            $table->string('verification_status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            // Indexes
            $table->index('verification_status');
            $table->index('country');
            $table->index('industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_profiles');
    }
};
