<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for payments table.
 *
 * Creates the payment tracking table for payment transactions.
 * Supports Razorpay integration with payment method details and refunds.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // Foreign key to subscription (optional)
            $table->uuid('subscription_id')->nullable();

            // Foreign key to invoice (optional)
            $table->uuid('invoice_id')->nullable();

            // Razorpay payment ID
            $table->string('razorpay_payment_id', 100)->nullable()->unique();

            // Razorpay order ID
            $table->string('razorpay_order_id', 100)->nullable();

            // Payment status (PaymentStatus enum)
            $table->string('status', 20)->default('created');

            // Amount and currency
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);

            // Payment method (card, upi, netbanking, etc.)
            $table->string('method', 50)->nullable();

            // Payment method details (JSON: masked card details, etc.)
            $table->json('method_details')->nullable();

            // Razorpay fees
            $table->decimal('fee', 10, 2)->nullable();
            $table->decimal('tax_on_fee', 10, 2)->nullable();

            // Error tracking
            $table->string('error_code', 100)->nullable();
            $table->text('error_description')->nullable();

            // Status timestamps
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Refund amount
            $table->decimal('refund_amount', 10, 2)->nullable();

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('razorpay_payment_id');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->nullOnDelete();

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
