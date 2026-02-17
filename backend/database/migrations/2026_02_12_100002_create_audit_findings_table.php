<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('audit_report_id');
            $table->string('type', 30); // stub, incomplete, missing, complete
            $table->string('severity', 20); // critical, high, medium, low
            $table->string('location', 500);
            $table->text('description');
            $table->text('evidence')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamp('fixed_at')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('audit_report_id');
            $table->index('type');
            $table->index('severity');
            $table->index('status');
            $table->index(['type', 'status'], 'audit_findings_type_status_idx');
            $table->index(['severity', 'status'], 'audit_findings_severity_status_idx');
            $table->index('created_at');
            $table->index('fixed_at');

            // Foreign key
            $table->foreign('audit_report_id')
                ->references('id')
                ->on('audit_reports')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
    }
};
