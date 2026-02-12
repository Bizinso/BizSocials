<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for payment_methods table.
 *
 * Creates the payment methods table for stored payment methods.
 * Supports tokenized payment methods from Razorpay.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // Razorpay token ID
            $table->string('razorpay_token_id', 100)->nullable();

            // Payment method type (PaymentMethodType enum)
            $table->string('type', 20);

            // Whether this is the default payment method
            $table->boolean('is_default')->default(false);

            // Payment method details (JSON: masked card details, VPA, etc.)
            $table->json('details');

            // Expiry date for cards
            $table->timestamp('expires_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_default']);
            $table->index('type');

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
