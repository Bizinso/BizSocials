<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_risk_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('whatsapp_business_account_id')->constrained('whatsapp_business_accounts')->cascadeOnDelete();
            $table->string('alert_type'); // quality_drop, rate_limit_hit, template_rejection_spike, suspension_risk, account_banned
            $table->string('severity'); // info, warning, critical
            $table->string('title');
            $table->text('description');
            $table->text('recommended_action')->nullable();
            $table->string('auto_action_taken')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->foreignUuid('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_business_account_id', 'severity', 'resolved_at'], 'acct_risk_alerts_wba_id_severity_resolved_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_risk_alerts');
    }
};
