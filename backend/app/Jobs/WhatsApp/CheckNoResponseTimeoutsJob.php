<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Enums\WhatsApp\WhatsAppAutomationTrigger;
use App\Enums\WhatsApp\WhatsAppConversationStatus;
use App\Models\WhatsApp\WhatsAppAutomationRule;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\WhatsAppAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CheckNoResponseTimeoutsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('inbox');
    }

    public function handle(WhatsAppAutomationService $automationService): void
    {
        // Find all active no_response_timeout rules
        $rules = WhatsAppAutomationRule::where('trigger_type', WhatsAppAutomationTrigger::NO_RESPONSE_TIMEOUT)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $timeoutMinutes = $rule->trigger_conditions['timeout_minutes'] ?? 30;

            // Find conversations with no agent response past the timeout
            $conversations = WhatsAppConversation::where('workspace_id', $rule->workspace_id)
                ->whereIn('status', [WhatsAppConversationStatus::ACTIVE, WhatsAppConversationStatus::PENDING])
                ->whereNotNull('last_customer_message_at')
                ->whereNull('first_response_at')
                ->where('last_customer_message_at', '<=', now()->subMinutes($timeoutMinutes))
                ->get();

            foreach ($conversations as $conversation) {
                try {
                    $automationService->executeRule($rule, $conversation);
                } catch (\Throwable $e) {
                    Log::warning('No-response timeout automation failed', [
                        'rule_id' => $rule->id,
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
