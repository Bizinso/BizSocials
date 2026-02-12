<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for invoices table.
 *
 * Creates the invoice tracking table for billing records.
 * Invoices support GST with CGST/SGST/IGST breakdown.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // Foreign key to subscription (optional)
            $table->uuid('subscription_id')->nullable();

            // Invoice number (auto-generated: BIZ/YYYY-YY/NNNNN)
            $table->string('invoice_number', 50)->unique();

            // Razorpay invoice ID
            $table->string('razorpay_invoice_id', 100)->nullable()->unique();

            // Invoice status (InvoiceStatus enum)
            $table->string('status', 20)->default('draft');

            // Currency (Currency enum)
            $table->string('currency', 3);

            // Amount breakdown
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2);

            // GST details (JSON: gstin, place_of_supply, cgst, sgst, igst, total_gst)
            $table->json('gst_details')->nullable();

            // Billing address snapshot (JSON)
            $table->json('billing_address');

            // Invoice line items (JSON)
            $table->json('line_items');

            // Dates
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // PDF download URL
            $table->string('pdf_url', 500)->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('issued_at');
            $table->index('due_at');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
