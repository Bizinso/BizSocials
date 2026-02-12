<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\AlertSeverity;
use App\Enums\WhatsApp\AlertType;
use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Enums\WhatsApp\WhatsAppQualityRating;
use App\Enums\WhatsApp\WhatsAppTemplateStatus;
use App\Models\WhatsApp\AccountRiskAlert;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppCampaign;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

final class WhatsAppGovernanceService extends BaseService
{
    public function evaluateAccountHealth(WhatsAppBusinessAccount $waba): void
    {
        $this->checkQualityRating($waba);
        $this->checkTemplateRejections($waba);
        $this->checkBlockRate($waba);
    }

    public function handleQualityDrop(
        WhatsAppBusinessAccount $waba,
        WhatsAppQualityRating $oldRating,
        WhatsAppQualityRating $newRating,
    ): void {
        $autoAction = null;

        if ($newRating === WhatsAppQualityRating::RED) {
            $waba->update(['is_marketing_enabled' => false]);
            $autoAction = 'marketing_disabled';
        }

        $severity = $newRating === WhatsAppQualityRating::RED
            ? AlertSeverity::CRITICAL
            : AlertSeverity::WARNING;

        AccountRiskAlert::create([
            'whatsapp_business_account_id' => $waba->id,
            'alert_type' => AlertType::QUALITY_DROP,
            'severity' => $severity,
            'title' => "Quality rating dropped from {$oldRating->label()} to {$newRating->label()}",
            'description' => "WhatsApp Business Account \"{$waba->name}\" quality rating has decreased. "
                . 'This may affect messaging limits and account standing.',
            'recommended_action' => $newRating === WhatsAppQualityRating::RED
                ? 'Review recent template content and reduce message frequency. Marketing has been auto-disabled.'
                : 'Monitor message quality and review templates with low engagement.',
            'auto_action_taken' => $autoAction,
        ]);

        $this->log('Quality rating drop detected', [
            'waba_id' => $waba->id,
            'old_rating' => $oldRating->value,
            'new_rating' => $newRating->value,
            'auto_action' => $autoAction,
        ]);
    }

    public function handleTemplateRejectionSpike(WhatsAppBusinessAccount $waba): void
    {
        $recentRejections = WhatsAppTemplate::whereHas('phoneNumber', function ($q) use ($waba) {
            $q->where('whatsapp_business_account_id', $waba->id);
        })
            ->where('status', WhatsAppTemplateStatus::REJECTED)
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        if ($recentRejections <= 3) {
            return;
        }

        $existing = AccountRiskAlert::where('whatsapp_business_account_id', $waba->id)
            ->where('alert_type', AlertType::TEMPLATE_REJECTION_SPIKE)
            ->whereNull('resolved_at')
            ->exists();

        if ($existing) {
            return;
        }

        AccountRiskAlert::create([
            'whatsapp_business_account_id' => $waba->id,
            'alert_type' => AlertType::TEMPLATE_REJECTION_SPIKE,
            'severity' => AlertSeverity::WARNING,
            'title' => "{$recentRejections} template rejections in the last 7 days",
            'description' => "Multiple templates have been rejected by Meta for account \"{$waba->name}\". "
                . 'Continued rejections may lead to account restrictions.',
            'recommended_action' => 'Review rejected templates and ensure they comply with WhatsApp Business Policy. '
                . 'Avoid re-submitting similar content.',
        ]);

        $this->log('Template rejection spike detected', [
            'waba_id' => $waba->id,
            'rejection_count' => $recentRejections,
        ]);
    }

    public function handleSuspensionRisk(WhatsAppBusinessAccount $waba): void
    {
        $waba->update(['is_marketing_enabled' => false]);

        AccountRiskAlert::create([
            'whatsapp_business_account_id' => $waba->id,
            'alert_type' => AlertType::SUSPENSION_RISK,
            'severity' => AlertSeverity::CRITICAL,
            'title' => "Account \"{$waba->name}\" at risk of suspension",
            'description' => 'This WhatsApp Business Account is at high risk of suspension due to policy violations '
                . 'or poor quality metrics. Marketing has been auto-disabled.',
            'recommended_action' => 'Immediately review all active campaigns and templates. Contact Meta support if needed.',
            'auto_action_taken' => 'marketing_disabled',
        ]);

        $this->log('Suspension risk detected', ['waba_id' => $waba->id]);
    }

    public function enforceRateLimits(WhatsAppPhoneNumber $phone): void
    {
        $threshold = (int) ($phone->daily_send_limit * 0.9);

        if ($phone->daily_send_count < $threshold) {
            return;
        }

        $waba = $phone->businessAccount;

        $existing = AccountRiskAlert::where('whatsapp_business_account_id', $waba->id)
            ->where('alert_type', AlertType::RATE_LIMIT_HIT)
            ->whereNull('resolved_at')
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if ($existing) {
            return;
        }

        AccountRiskAlert::create([
            'whatsapp_business_account_id' => $waba->id,
            'alert_type' => AlertType::RATE_LIMIT_HIT,
            'severity' => AlertSeverity::WARNING,
            'title' => "Phone {$phone->phone_number} approaching daily send limit",
            'description' => "Sent {$phone->daily_send_count} of {$phone->daily_send_limit} daily messages. "
                . 'Further sends may be blocked.',
            'recommended_action' => 'Reduce message volume or wait until the daily limit resets.',
        ]);

        $this->log('Rate limit approaching', [
            'phone_id' => $phone->id,
            'sent' => $phone->daily_send_count,
            'limit' => $phone->daily_send_limit,
        ]);
    }

    public function autoThrottle(WhatsAppCampaign $campaign): void
    {
        $phone = $campaign->phoneNumber;
        $waba = $phone->businessAccount;

        if ($waba->quality_rating === WhatsAppQualityRating::RED) {
            $campaign->update(['status' => \App\Enums\WhatsApp\WhatsAppCampaignStatus::CANCELLED]);

            $this->log('Campaign auto-cancelled due to red quality rating', [
                'campaign_id' => $campaign->id,
                'waba_id' => $waba->id,
            ]);
        }
    }

    public function listAlerts(array $filters = []): LengthAwarePaginator
    {
        $query = AccountRiskAlert::with('businessAccount')
            ->orderByDesc('created_at');

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        if (isset($filters['resolved']) && $filters['resolved'] === false) {
            $query->whereNull('resolved_at');
        }

        if (isset($filters['waba_id'])) {
            $query->where('whatsapp_business_account_id', $filters['waba_id']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function listAllAccounts(array $filters = []): LengthAwarePaginator
    {
        $query = WhatsAppBusinessAccount::with(['phoneNumbers', 'tenant'])
            ->withCount([
                'alerts as unresolved_alerts_count' => function ($q) {
                    $q->whereNull('resolved_at');
                },
            ])
            ->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['quality_rating'])) {
            $query->where('quality_rating', $filters['quality_rating']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function getAccountDetail(WhatsAppBusinessAccount $waba): array
    {
        $waba->load(['phoneNumbers', 'tenant']);

        $alerts = AccountRiskAlert::where('whatsapp_business_account_id', $waba->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'account' => $waba,
            'alerts' => $alerts,
            'stats' => [
                'total_conversations' => $waba->conversations()->count(),
                'active_conversations' => $waba->conversations()->where('status', 'active')->count(),
                'total_templates' => WhatsAppTemplate::whereHas('phoneNumber', function ($q) use ($waba) {
                    $q->where('whatsapp_business_account_id', $waba->id);
                })->count(),
                'approved_templates' => WhatsAppTemplate::whereHas('phoneNumber', function ($q) use ($waba) {
                    $q->where('whatsapp_business_account_id', $waba->id);
                })->where('status', WhatsAppTemplateStatus::APPROVED)->count(),
            ],
        ];
    }

    public function reactivateAccount(WhatsAppBusinessAccount $waba): void
    {
        $waba->update([
            'status' => WhatsAppAccountStatus::VERIFIED,
            'suspended_reason' => null,
        ]);

        AccountRiskAlert::where('whatsapp_business_account_id', $waba->id)
            ->whereNull('resolved_at')
            ->where('alert_type', AlertType::SUSPENSION_RISK)
            ->update(['resolved_at' => now()]);

        $this->log('WhatsApp account reactivated', ['waba_id' => $waba->id]);
    }

    public function disableMarketing(WhatsAppBusinessAccount $waba): void
    {
        $waba->update(['is_marketing_enabled' => false]);
        $this->log('Marketing disabled for account', ['waba_id' => $waba->id]);
    }

    public function enableMarketing(WhatsAppBusinessAccount $waba): void
    {
        if ($waba->quality_rating === WhatsAppQualityRating::RED) {
            throw new \RuntimeException('Cannot enable marketing with RED quality rating');
        }

        $waba->update(['is_marketing_enabled' => true]);
        $this->log('Marketing enabled for account', ['waba_id' => $waba->id]);
    }

    public function overrideRateLimit(WhatsAppPhoneNumber $phone, int $newLimit): void
    {
        $phone->update(['daily_send_limit' => $newLimit]);
        $this->log('Rate limit overridden', ['phone_id' => $phone->id, 'new_limit' => $newLimit]);
    }

    public function getConsentLogs(WhatsAppBusinessAccount $waba): array
    {
        return [
            'compliance_accepted_at' => $waba->compliance_accepted_at?->toIso8601String(),
            'compliance_accepted_by' => $waba->complianceAcceptedBy?->name ?? null,
            'account_created_at' => $waba->created_at->toIso8601String(),
            'marketing_enabled' => $waba->is_marketing_enabled,
            'status' => $waba->status->label(),
        ];
    }

    private function checkQualityRating(WhatsAppBusinessAccount $waba): void
    {
        foreach ($waba->phoneNumbers as $phone) {
            if ($phone->quality_rating === WhatsAppQualityRating::RED) {
                $this->handleQualityDrop($waba, WhatsAppQualityRating::YELLOW, WhatsAppQualityRating::RED);

                return;
            }
        }
    }

    private function checkTemplateRejections(WhatsAppBusinessAccount $waba): void
    {
        $this->handleTemplateRejectionSpike($waba);
    }

    private function checkBlockRate(WhatsAppBusinessAccount $waba): void
    {
        $workspaceIds = $waba->conversations()->distinct()->pluck('workspace_id');

        $totalBlocks = \App\Models\WhatsApp\WhatsAppDailyMetric::whereIn('workspace_id', $workspaceIds)
            ->where('date', '>=', now()->subDays(7))
            ->sum('block_count');

        if ($totalBlocks > 50) {
            $this->handleSuspensionRisk($waba);
        }
    }
}
